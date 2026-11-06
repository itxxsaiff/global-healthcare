<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $service) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->practitionerProfile;
        $tab = in_array($request->query('tab'), ['requests', 'upcoming', 'past'], true) ? $request->query('tab') : 'requests';

        $bookings = $profile->bookings()->with('patient:id,name')
            ->when($tab === 'requests', fn ($q) => $q->where('status', BookingStatus::Requested)->oldest('starts_at'))
            ->when($tab === 'upcoming', fn ($q) => $q->whereIn('status', [BookingStatus::AwaitingPayment, BookingStatus::Confirmed])->where('ends_at', '>=', now())->oldest('starts_at'))
            ->when($tab === 'past', fn ($q) => $q->whereNotIn('status', BookingStatus::HOLDING)->latest('starts_at'))
            ->paginate(20)->withQueryString();

        return view('practitioner.bookings.index', compact('bookings', 'tab', 'profile'));
    }

    /** JSON feed for the FullCalendar view (practitioner's own bookings only). */
    public function calendar(Request $request): JsonResponse
    {
        $profile = $request->user()->practitionerProfile;
        $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date']]);

        $events = $profile->bookings()->with('patient:id,name')
            ->whereIn('status', [BookingStatus::Requested, BookingStatus::AwaitingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereBetween('starts_at', [$request->date('start'), $request->date('end')])
            ->get()->map(fn (Booking $b) => [
                'title' => $b->patient->name,
                'start' => $b->starts_at->toIso8601String(),
                'end' => $b->ends_at->toIso8601String(),
                'url' => route('practitioner.bookings.show', $b),
                'classNames' => ['fc-status-'.$b->status->value],
            ]);

        return response()->json($events);
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeOwner($request, $booking);
        $booking->load(['patient.patientProfile', 'history.actor:id,name', 'latestPayment']);

        return view('practitioner.bookings.show', ['booking' => $booking, 'tz' => $request->user()->practitionerProfile->timezone]);
    }

    public function accept(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $data = $request->validate(['meeting_details' => ['nullable', 'string', 'max:500']]);
        $booking = $this->service->accept($booking, $request->user(), $data['meeting_details'] ?? null);

        return back()->with('success', $booking->status === BookingStatus::Confirmed
            ? 'Booking confirmed. The patient has been notified.'
            : 'Accepted. The patient has been sent a secure payment link, and you\'ll be notified when they pay.');
    }

    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->service->reject($booking, $request->user(), $data['reason'] ?? null);

        return redirect()->route('practitioner.bookings.index')->with('success', 'Request declined. The patient has been notified.');
    }

    public function meeting(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_unless($booking->status->holdsSlot(), 422);
        $data = $request->validate(['meeting_details' => ['required', 'string', 'max:500']]);
        $booking->update(['meeting_details' => $data['meeting_details']]);

        return back()->with('success', 'Joining details saved. The patient can see them in their dashboard.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->service->cancel($booking, $request->user(), $data['reason']);

        return back()->with('success', 'Booking cancelled. The patient has been notified'.($booking->fresh()->refund_due ? ' and will be refunded.' : '.'));
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $this->service->complete($booking, $request->user());

        return back()->with('success', 'Marked as completed. The patient can now leave a review.');
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->practitioner_profile_id === $request->user()->practitionerProfile?->id, 404);
    }
}
