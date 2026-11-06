<?php

namespace App\Payments\Gateways;

use App\Enums\PlanInterval;
use App\Models\Membership;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe Checkout. USD memberships use subscription mode (auto-debit, per the
 * client document); renewals and failures arrive by webhook.
 * Requires a Stripe account on a Stripe-supported business entity.
 */
class StripeGateway implements PaymentGateway
{
    private ?StripeClient $client = null;

    public function __construct(private readonly array $config) {}

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Card (Stripe)';
    }

    public function supports(Payment $payment): bool
    {
        return filled($this->config['secret'] ?? null) && $payment->currency !== 'PKR';
    }

    public function createCheckout(Payment $payment): CheckoutResult
    {
        $payable = $payment->payable;
        $recurring = $payable instanceof Membership && $payable->auto_renew && $payable->plan->interval !== PlanInterval::None;

        $lineItem = [
            'quantity' => 1,
            'price_data' => array_filter([
                'currency' => strtolower($payment->currency),
                'unit_amount' => $payment->amount_minor,
                'product_data' => ['name' => $payable instanceof Membership ? config('app.name').' – '.$payable->plan->name : 'Consultation'],
                'recurring' => $recurring ? match ($payable->plan->interval) {
                    PlanInterval::Month => ['interval' => 'month'],
                    PlanInterval::SixMonths => ['interval' => 'month', 'interval_count' => 6],
                    PlanInterval::Year => ['interval' => 'year'],
                } : null,
            ]),
        ];

        $session = $this->client()->checkout->sessions->create(array_filter([
            'mode' => $recurring ? 'subscription' : 'payment',
            'line_items' => [$lineItem],
            'client_reference_id' => $payment->uuid,
            'customer_email' => $payment->user->email,
            'metadata' => ['payment_uuid' => $payment->uuid],
            'subscription_data' => $recurring ? ['metadata' => ['membership_id' => (string) $payable->id]] : null,
            'success_url' => route('payments.return', ['payment' => $payment, 'gateway' => 'stripe']).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.return', ['payment' => $payment, 'gateway' => 'stripe', 'cancelled' => 1]),
            'expires_at' => now()->addMinutes(60)->timestamp,
        ]));

        return CheckoutResult::redirect($session->url, $session->id);
    }

    /** Webhook: signature verified with the endpoint secret. */
    public function parseCallback(Request $request): GatewayEvent
    {
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), $this->config['webhook_secret']);
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            abort(400, 'Invalid Stripe signature.');
        }

        $o = $event->data->object;

        return match ($event->type) {
            'checkout.session.completed' => new GatewayEvent('stripe', $event->id,
                $o->payment_status === 'paid' || $o->mode === 'subscription' ? GatewayEvent::SUCCEEDED : GatewayEvent::PENDING,
                paymentUuid: $o->metadata->payment_uuid ?? $o->client_reference_id, gatewayReference: $o->id,
                amountMinor: $o->amount_total, currency: strtoupper((string) $o->currency),
                subscriptionId: $o->subscription ?? null, customerId: $o->customer ?? null, type: $event->type),
            'invoice.paid' => new GatewayEvent('stripe', $event->id,
                ($o->billing_reason ?? '') === 'subscription_create' ? GatewayEvent::IGNORED : GatewayEvent::RENEWED,
                subscriptionId: $o->subscription ?? null, amountMinor: $o->amount_paid, currency: strtoupper((string) $o->currency),
                periodEnd: $o->lines->data[0]->period->end ?? null, type: $event->type),
            'invoice.payment_failed' => new GatewayEvent('stripe', $event->id, GatewayEvent::RENEWAL_FAILED,
                subscriptionId: $o->subscription ?? null, type: $event->type),
            'customer.subscription.deleted' => new GatewayEvent('stripe', $event->id, GatewayEvent::CANCELLED,
                subscriptionId: $o->id, type: $event->type),
            default => new GatewayEvent('stripe', $event->id, GatewayEvent::IGNORED, type: $event->type),
        };
    }

    public function verify(Payment $payment, GatewayEvent $event): bool
    {
        $session = $this->client()->checkout->sessions->retrieve($event->gatewayReference ?? $payment->gateway_reference);

        return ($session->metadata->payment_uuid ?? $session->client_reference_id) === $payment->uuid
            && (int) $session->amount_total === $payment->amount_minor
            && in_array($session->payment_status, ['paid', 'no_payment_required'], true);
    }

    /** Stripe-hosted page where practitioners update their card or cancel. */
    public function billingPortalUrl(string $customerId, string $returnUrl): string
    {
        return $this->client()->billingPortal->sessions->create(['customer' => $customerId, 'return_url' => $returnUrl])->url;
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        $this->client()->subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);
    }

    private function client(): StripeClient
    {
        if (blank($this->config['secret'] ?? null)) {
            throw new RuntimeException('Stripe is not configured. Set STRIPE_SECRET in .env.');
        }

        return $this->client ??= new StripeClient($this->config['secret']);
    }
}
