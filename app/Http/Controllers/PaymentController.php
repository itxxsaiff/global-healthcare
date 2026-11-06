<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentManager;
use App\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /** Where the gateway sends the browser back. Verified server-side before anything changes. */
    public function return(Request $request, Payment $payment, string $gateway, PaymentManager $gateways, PaymentService $payments): RedirectResponse
    {
        abort_unless($payment->gateway === $gateway, 404);

        if ($gateway === 'stripe') {
            // Stripe's source of truth is the webhook; on return we verify the session directly.
            if (! $request->boolean('cancelled') && $request->filled('session_id') && $payment->status === PaymentStatus::Pending) {
                $payments->handle(new GatewayEvent('stripe', 'return-'.$request->query('session_id'), GatewayEvent::SUCCEEDED,
                    paymentUuid: $payment->uuid, gatewayReference: (string) $request->query('session_id'), type: 'return'));
            }
        } elseif ($gateway !== 'fake') {
            $payments->handle($gateways->driver($gateway)->parseCallback($request));
        }

        return $this->afterPayment($payment->fresh());
    }

    // ── Development gateway ─────────────────────────────────────────

    public function fakeShow(Request $request, Payment $payment): View
    {
        abort_unless($request->hasValidSignature() && ! app()->isProduction(), 403);

        return view('payments.fake', ['payment' => $payment->load('payable')]);
    }

    public function fakeComplete(Request $request, Payment $payment, PaymentManager $gateways, PaymentService $payments): RedirectResponse
    {
        abort_if(app()->isProduction(), 403);
        $payments->handle($gateways->driver('fake')->parseCallback($request));

        return $this->afterPayment($payment->fresh());
    }

    private function afterPayment(Payment $payment): RedirectResponse
    {
        $ok = $payment->status === PaymentStatus::Paid;
        $payable = $payment->payable;

        $route = $payable instanceof Booking
            ? route('patient.bookings.show', $payable)
            : route('practitioner.membership.show');

        return redirect($route)->with($ok ? 'success' : 'warning', $ok
            ? ($payable instanceof Booking ? 'Payment received. Your consultation is confirmed!' : 'Payment received. Your plan is active and your profile is live.')
            : 'The payment was not completed. You have not been charged, and you can try again.');
    }
}
