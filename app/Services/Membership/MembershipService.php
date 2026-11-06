<?php

namespace App\Services\Membership;

use App\Enums\Market;
use App\Enums\MembershipStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\Setting;
use App\Notifications\MembershipActivated;
use App\Notifications\MembershipExpiring;
use App\Notifications\MembershipLapsed;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Practitioner plans (client doc §3 step 3 and §5):
 *  - PK Plan 1: free, active immediately, 20% commission.
 *  - PK Plan 2: PKR, paid per period (manual renewal: local gateways don't auto-debit).
 *  - International: USD via Stripe subscription (auto-debit), hidden if it lapses.
 */
class MembershipService
{
    public function __construct(private readonly VisibilityService $visibility) {}

    /** @return Collection<int, Plan> */
    public function plansFor(PractitionerProfile $profile): Collection
    {
        return Plan::query()->active()->where('market', $profile->market)->get();
    }

    /** Returns null when activated immediately (free plan), or a checkout to redirect to. */
    public function choose(PractitionerProfile $profile, Plan $plan): ?CheckoutResult
    {
        if (! in_array($profile->verification_status, [VerificationStatus::Approved], true)) {
            throw ValidationException::withMessages(['plan' => 'Plans unlock once your profile is verified.']);
        }
        if ($plan->market !== $profile->market || ! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => 'This plan is not available for your country.']);
        }

        if ($plan->isFree()) {
            DB::transaction(function () use ($profile, $plan) {
                $this->closeCurrent($profile);
                $profile->memberships()->create([
                    'plan_id' => $plan->id,
                    'status' => MembershipStatus::Active,
                    'starts_at' => now(),
                    'ends_at' => null,
                ]);
                AuditLog::record('membership.activated', $profile, ['plan' => $plan->code]);
            });
            $this->visibility->refresh($profile);
            $profile->user->notify(new MembershipActivated($profile->fresh('activeMembership.plan')->activeMembership));

            return null;
        }

        // Paid plan: pending until the payment is confirmed.
        $membership = $profile->memberships()->create([
            'plan_id' => $plan->id,
            'status' => MembershipStatus::PendingPayment,
            'auto_renew' => $profile->market === Market::Intl,
            'gateway' => config("platform.gateways.{$profile->market->value}"),
        ]);

        return app(PaymentService::class)->start(
            $membership, $profile->user, $membership->gateway, $plan->price_minor, $plan->currency, now()->addDay(),
        );
    }

    /** Called by PaymentService inside its transaction once money is confirmed. */
    public function paymentReceived(Membership $membership, Payment $payment, GatewayEvent $event): void
    {
        $profile = $membership->practitioner;
        $current = $profile->activeMembership;

        // Renewing the same paid plan early: extend from the current end date instead of losing days.
        $start = ($current && $current->plan_id === $membership->plan_id && $current->ends_at?->isFuture()) ? $current->ends_at : now();

        $this->closeCurrent($profile, except: $membership);
        $membership->forceFill([
            'status' => MembershipStatus::Active,
            'starts_at' => $start,
            'ends_at' => $membership->plan->interval->addTo($start),
            'gateway_subscription_id' => $event->subscriptionId ?? $membership->gateway_subscription_id,
            'gateway_customer_id' => $event->customerId ?? $membership->gateway_customer_id,
        ])->save();

        AuditLog::record('membership.activated', $profile, ['plan' => $membership->plan->code, 'payment' => $payment->uuid]);

        DB::afterCommit(function () use ($profile, $membership) {
            $this->visibility->refresh($profile->fresh());
            $profile->user->notify(new MembershipActivated($membership));
        });
    }

    /** Stripe subscription lifecycle after the first payment. */
    public function subscriptionEvent(GatewayEvent $event): void
    {
        $membership = Membership::query()->where('gateway_subscription_id', $event->subscriptionId)->latest('id')->first();
        if (! $membership) {
            return;
        }

        match ($event->outcome) {
            GatewayEvent::RENEWED => $membership->forceFill([
                'status' => MembershipStatus::Active,
                'ends_at' => $event->periodEnd ? \Illuminate\Support\Carbon::createFromTimestamp($event->periodEnd) : $membership->plan->interval->addTo($membership->ends_at ?? now()),
            ])->save(),
            GatewayEvent::RENEWAL_FAILED => $membership->forceFill(['status' => MembershipStatus::PastDue])->save(),
            GatewayEvent::CANCELLED => $membership->forceFill(['status' => MembershipStatus::Expired, 'cancelled_at' => now()])->save(),
            default => null,
        };

        $this->visibility->refresh($membership->practitioner);

        if (in_array($event->outcome, [GatewayEvent::RENEWAL_FAILED, GatewayEvent::CANCELLED], true)) {
            $membership->practitioner->user->notify(new MembershipLapsed($membership));
        }
    }

    /**
     * Scheduled hourly. Ends memberships past their end date:
     *  - PK paid plan → falls back to the free plan (stays listed, 20% commission);
     *  - auto-renewing (Stripe) → waits for the webhook; hidden after the grace period;
     *  - everything else → expired, profile hidden.
     */
    public function expireDue(): int
    {
        $grace = (int) Setting::get('membership_grace_days', 3);
        $count = 0;

        Membership::query()
            ->with(['plan', 'practitioner.user'])
            ->where(fn ($q) => $q
                ->where(fn ($a) => $a->where('status', MembershipStatus::Active)->whereNotNull('ends_at')->where('ends_at', '<', now()))
                ->orWhere(fn ($p) => $p->where('status', MembershipStatus::PastDue)->where('ends_at', '<', now()->subDays($grace))))
            ->chunkById(100, function ($memberships) use (&$count, $grace) {
                foreach ($memberships as $m) {
                    $profile = $m->practitioner;

                    if ($m->status === MembershipStatus::Active && $m->auto_renew && $m->ends_at->gt(now()->subDays($grace))) {
                        $m->forceFill(['status' => MembershipStatus::PastDue])->save();
                        continue;
                    }

                    $m->forceFill(['status' => MembershipStatus::Expired])->save();
                    $count++;

                    $fallback = Plan::query()->where('code', Setting::get('pk_paid_expiry_fallback'))->first();
                    if ($profile->market === Market::Pk && $fallback && $fallback->market === Market::Pk && ! $m->plan->isFree()) {
                        $profile->memberships()->create(['plan_id' => $fallback->id, 'status' => MembershipStatus::Active, 'starts_at' => now()]);
                    }

                    $this->visibility->refresh($profile);
                    $profile->user->notify(new MembershipLapsed($m));
                }
            });

        return $count;
    }

    /** Scheduled daily: reminders before PK paid plans end (no auto-debit there). */
    public function sendRenewalReminders(): void
    {
        foreach ((array) Setting::get('renewal_reminder_days', [7, 3, 1]) as $days) {
            Membership::query()->with(['plan', 'practitioner.user'])
                ->where('status', MembershipStatus::Active)->where('auto_renew', false)
                ->whereBetween('ends_at', [now()->addDays($days)->startOfDay(), now()->addDays($days)->endOfDay()])
                ->each(fn (Membership $m) => $m->practitioner->user->notify(new MembershipExpiring($m, $days)));
        }
    }

    private function closeCurrent(PractitionerProfile $profile, ?Membership $except = null): void
    {
        $profile->memberships()
            ->whereIn('status', [MembershipStatus::Active, MembershipStatus::PastDue])
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->update(['status' => MembershipStatus::Cancelled, 'cancelled_at' => now()]);
    }
}
