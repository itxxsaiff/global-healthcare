<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Commission\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = BookingStatus::tryFrom((string) $request->query('status'));
        $q = trim((string) $request->query('q'));

        $bookings = Booking::query()
            ->with(['patient:id,name', 'practitioner' => fn ($p) => $p->with('user:id,name')])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->boolean('refunds'), fn ($query) => $query->where('refund_due', true))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('reference', 'like', "%{$q}%")
                ->orWhereHas('patient', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                ->orWhereHas('practitioner.user', fn ($u) => $u->where('name', 'like', "%{$q}%"))))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'status' => $status,
            'refundCount' => Booking::query()->where('refund_due', true)->count(),
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', ['booking' => $booking->load(['patient', 'practitioner.user', 'history.actor:id,name', 'payments', 'review'])]);
    }

    /** Admin refunds in the gateway's merchant portal, then records it here (reverses the ledger). */
    public function refunded(Request $request, Booking $booking, CommissionService $commission): RedirectResponse
    {
        abort_unless($booking->refund_due, 422);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:300']]);

        DB::transaction(function () use ($booking, $commission) {
            $booking->payments()->where('status', PaymentStatus::Paid)->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now()]);
            $commission->reverseForRefund($booking);
            $booking->forceFill(['refund_due' => false])->save();
        });
        AuditLog::record('booking.refunded', $booking, ['note' => $data['note'] ?? null]);

        return back()->with('success', 'Refund recorded and the commission ledger reversed.');
    }

    public function cancel(Request $request, Booking $booking, BookingService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $service->cancel($booking, $request->user(), $data['reason']);
        AuditLog::record('booking.admin_cancelled', $booking, ['reason' => $data['reason']]);

        return back()->with('success', 'Booking cancelled; both sides notified.');
    }
}
