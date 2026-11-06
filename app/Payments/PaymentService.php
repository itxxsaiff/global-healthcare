<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use App\Services\Booking\BookingService;
use App\Services\Membership\MembershipService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates payments, and turns verified gateway events into business effects.
 *
 * Guarantees:
 *  - idempotent: an event id is processed at most once (unique payment_events row),
 *    and a payment already marked paid is never processed again;
 *  - never trusts the browser: success is re-verified with the provider when possible;
 *  - amount and currency must match what we asked for.
 */
class PaymentService
{
    public function __construct(private readonly PaymentManager $gateways) {}

    public function start(Booking|Membership $payable, User $payer, string $gatewayKey, int $amountMinor, string $currency, ?\DateTimeInterface $expiresAt = null): CheckoutResult
    {
        $gateway = $this->gateways->driver($gatewayKey);

        $payment = DB::transaction(function () use ($payable, $payer, $gatewayKey, $amountMinor, $currency, $expiresAt) {
            // Re-use an open checkout for the same thing instead of piling up attempts.
            $payable->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Expired]);

            $payment = new Payment([
                'user_id' => $payer->id,
                'gateway' => $gatewayKey,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'status' => PaymentStatus::Pending,
                'expires_at' => $expiresAt,
            ]);
            $payment->payable()->associate($payable);
            $payment->save();

            return $payment;
        });

        abort_unless($gateway->supports($payment), 422, 'This payment method is not available for this payment.');

        $result = $gateway->createCheckout($payment->load('payable', 'user'));
        $payment->forceFill(['gateway_reference' => $result->reference])->save();

        return $result;
    }

    /** Entry point for both browser returns and server webhooks. */
    public function handle(GatewayEvent $event): ?Payment
    {
        if (! $this->recordEvent($event)) {
            return $event->paymentUuid ? Payment::query()->where('uuid', $event->paymentUuid)->first() : null; // replay
        }

        return match ($event->outcome) {
            GatewayEvent::SUCCEEDED => $this->succeeded($event),
            GatewayEvent::FAILED => $this->failed($event),
            GatewayEvent::RENEWED, GatewayEvent::RENEWAL_FAILED, GatewayEvent::CANCELLED => $this->subscriptionEvent($event),
            default => null,
        };
    }

    private function succeeded(GatewayEvent $event): ?Payment
    {
        $payment = Payment::query()->where('uuid', $event->paymentUuid)->first();
        if (! $payment) {
            Log::warning('Payment event for unknown payment', ['event' => $event->eventId]);

            return null;
        }

        if ($event->amountMinor !== null && ($event->amountMinor !== $payment->amount_minor || ($event->currency && $event->currency !== $payment->currency))) {
            Log::critical('Payment amount mismatch', ['payment' => $payment->uuid, 'event' => $event->eventId]);
            $this->markEvent($event, 'amount mismatch');

            return $payment;
        }

        $gateway = $this->gateways->driver($payment->gateway);
        if (! $gateway->verify($payment, $event)) {
            $this->markEvent($event, 'provider verification failed');

            return $payment;
        }

        DB::transaction(function () use ($payment, $event) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if ($locked->status === PaymentStatus::Paid) {
                // Already processed (e.g. browser return beat the webhook). Backfill
                // subscription identifiers that only the webhook carries.
                if ($locked->payable instanceof Membership && $event->subscriptionId && ! $locked->payable->gateway_subscription_id) {
                    $locked->payable->forceFill([
                        'gateway_subscription_id' => $event->subscriptionId,
                        'gateway_customer_id' => $event->customerId,
                    ])->save();
                }

                return;
            }

            $locked->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'gateway_reference' => $event->gatewayReference ?? $locked->gateway_reference,
                'meta' => array_filter(['subscription' => $event->subscriptionId, 'customer' => $event->customerId]),
            ])->save();

            $payable = $locked->payable;
            match (true) {
                $payable instanceof Booking => app(BookingService::class)->paymentReceived($payable, $locked),
                $payable instanceof Membership => app(MembershipService::class)->paymentReceived($payable, $locked, $event),
            };
        });

        $this->markEvent($event);

        return $payment->fresh();
    }

    private function failed(GatewayEvent $event): ?Payment
    {
        $payment = Payment::query()->where('uuid', $event->paymentUuid)->first();
        if ($payment && $payment->status === PaymentStatus::Pending) {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();
        }
        $this->markEvent($event);

        return $payment;
    }

    private function subscriptionEvent(GatewayEvent $event): ?Payment
    {
        app(MembershipService::class)->subscriptionEvent($event);
        $this->markEvent($event);

        return null;
    }

    /** @return bool false if this exact event was already received (replay). */
    private function recordEvent(GatewayEvent $event): bool
    {
        try {
            PaymentEvent::query()->create([
                'gateway' => $event->gateway,
                'event_id' => $event->eventId,
                'type' => $event->type,
                'payment_id' => $event->paymentUuid ? Payment::query()->where('uuid', $event->paymentUuid)->value('id') : null,
                'payload' => $event->payload,
                'signature_valid' => true,
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    private function markEvent(GatewayEvent $event, ?string $error = null): void
    {
        PaymentEvent::query()->where('gateway', $event->gateway)->where('event_id', $event->eventId)
            ->update(['processed_at' => now(), 'error' => $error]);
    }
}
