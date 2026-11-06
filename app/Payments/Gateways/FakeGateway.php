<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Development/testing gateway: a local page with "pay" and "fail" buttons.
 * Lets the whole booking and membership flow run before merchant accounts exist.
 * Refuses to load in production (see PaymentManager).
 */
class FakeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'Test payment';
    }

    public function supports(Payment $payment): bool
    {
        return true;
    }

    public function createCheckout(Payment $payment): CheckoutResult
    {
        return CheckoutResult::redirect(
            URL::temporarySignedRoute('payments.fake.show', now()->addHour(), $payment),
            'FAKE-'.Str::upper(Str::random(10)),
        );
    }

    public function parseCallback(Request $request): GatewayEvent
    {
        abort_unless($request->hasValidSignature(), 403);

        return new GatewayEvent(
            gateway: 'fake',
            eventId: 'fake-'.Str::uuid(),
            outcome: $request->input('outcome') === 'fail' ? GatewayEvent::FAILED : GatewayEvent::SUCCEEDED,
            paymentUuid: $request->route('payment')?->uuid,
            type: 'fake.'.$request->input('outcome', 'pay'),
        );
    }

    public function verify(Payment $payment, GatewayEvent $event): bool
    {
        return true;
    }
}
