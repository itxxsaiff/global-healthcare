<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use Illuminate\Http\Request;

/**
 * Every payment provider implements this. The booking and membership modules
 * only ever talk to this interface, so providers can be swapped or added
 * without touching business logic.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /** Can this gateway take money for the given payment (currency, recurring…)? */
    public function supports(Payment $payment): bool;

    /** Start a hosted checkout: returns a redirect URL or an auto-submitting form. */
    public function createCheckout(Payment $payment): CheckoutResult;

    /** Verify the authenticity of a browser return or server webhook and normalise it. */
    public function parseCallback(Request $request): GatewayEvent;

    /** Ask the provider directly what happened (never trust the browser redirect alone). */
    public function verify(Payment $payment, GatewayEvent $event): bool;
}
