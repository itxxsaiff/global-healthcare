<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Payments\Gateways\StripeGateway;
use App\Payments\PaymentManager;
use App\Services\Membership\MembershipService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function show(Request $request): View
    {
        $profile = $request->user()->practitionerProfile->load(['activeMembership.plan', 'memberships' => fn ($q) => $q->with('plan')->latest()->limit(10)]);

        return view('practitioner.membership', [
            'profile' => $profile,
            'current' => $profile->activeMembership,
            'plans' => $this->memberships->plansFor($profile),
            'unlocked' => $profile->verification_status === VerificationStatus::Approved,
        ]);
    }

    public function choose(Request $request, Plan $plan)
    {
        $profile = $request->user()->practitionerProfile;
        $checkout = $this->memberships->choose($profile, $plan);

        if (! $checkout) {
            return redirect()->route('practitioner.membership.show')->with('success', "You're on the {$plan->name} plan and your profile is live.");
        }

        return $checkout->redirectUrl ? redirect()->away($checkout->redirectUrl) : view('payments.redirect', ['checkout' => $checkout]);
    }

    /** Stripe customer portal: update card, cancel auto-renewal. */
    public function portal(Request $request, PaymentManager $gateways)
    {
        $membership = $request->user()->practitionerProfile->activeMembership;
        abort_unless($membership?->gateway === 'stripe' && $membership->gateway_customer_id, 404);

        /** @var StripeGateway $stripe */
        $stripe = $gateways->driver('stripe');

        return redirect()->away($stripe->billingPortalUrl($membership->gateway_customer_id, route('practitioner.membership.show')));
    }
}
