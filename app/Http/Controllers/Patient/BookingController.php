<?php

namespace App\Http\Controllers\Patient;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Payments\PaymentService;
use App\Services\Booking\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'past' ? 'past' : 'upcoming';
        $bookings = $request->user()->bookings()
            ->with(['practitioner' => fn ($q) => $q->with(['user:id,name', 'primarySpecialty:id,practitioner_label']), 'review:id,booking_id'])
            ->when($tab === 'upcoming', fn ($q) => $q->whereIn('status', BookingStatus::HOLDING)->where('ends_at', '>=', now())->oldest('starts_at'))
            ->when($tab === 'past', fn ($q) => $q->where(fn ($w) => $w->whereNotIn('status', BookingStatus::HOLDING)->orWhere('ends_at', '<', now()))->latest('starts_at'))
            ->paginate(15)->withQueryString();

        return view('patient.bookings.index', compact('bookings', 'tab'));
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeOwner($request, $booking);
        $booking->load(['practitioner' => fn ($q) => $q->with(['user:id,name', 'primarySpecialty']), 'history', 'latestPayment', 'review']);

        return view('patient.bookings.show', ['booking' => $booking, 'tz' => $request->user()->timezone]);
    }

    public function pay(Request $request, Booking $booking, PaymentService $payments)
    {
        $this->authorizeOwner($request, $booking);
        abort_unless($booking->needsPayment() && $booking->payment_due_at?->isFuture(), 422, 'This booking does not need payment.');

        $gateway = config('platform.gateways.'.($booking->currency === 'PKR' ? 'pk' : 'intl'));
        $checkout = $payments->start($booking, $request->user(), $gateway, $booking->fee_minor, $booking->currency, $booking->payment_due_at);

        return $checkout->redirectUrl
            ? redirect()->away($checkout->redirectUrl)
            : view('payments.redirect', ['checkout' => $checkout]);
    }

    public function cancel(Request $request, Booking $booking, BookingService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $booking = $service->cancel($booking, $request->user(), $data['reason'] ?? null);

        return back()->with('success', $booking->refund_due ? 'Booking cancelled. Your refund is being processed.' : 'Booking cancelled.');
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->patient_id === $request->user()->id, 404);
    }
}
