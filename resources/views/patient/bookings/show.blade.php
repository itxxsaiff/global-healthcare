@extends('layouts.dashboard')
@section('title', 'Booking '.$booking->reference)
@section('crumb', 'Booking '.$booking->reference)
@php
    $doc = $booking->practitioner;
    $s = $booking->status;
    $start = $booking->startsAtFor($tz);
    $freeCancelHours = (int) \App\Models\Setting::get('free_cancellation_hours', 24);
@endphp
@section('content')
<a href="{{ route('patient.bookings.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> All bookings</a>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card-soft p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <div class="d-flex gap-3 align-items-center">
                    <x-avatar :profile="$doc" size="lg" />
                    <div>
                        <h1 class="h3 mb-0">{{ $doc->displayName() }}</h1>
                        <div class="text-forest fw-semibold">{{ $doc->primarySpecialty?->practitioner_label }}</div>
                        <div class="small text-muted">Ref {{ $booking->reference }}</div>
                    </div>
                </div>
                <x-status :status="$s" />
            </div>
            <div class="row g-3 border-top pt-3">
                <div class="col-sm-6"><div class="small text-muted">When</div><div class="fw-semibold">{{ $start->format('l j F Y') }}<br>{{ $start->format('g:i A') }} – {{ $booking->ends_at->copy()->setTimezone($tz)->format('g:i A') }}</div><div class="small text-muted">{{ str_replace('_', ' ', $tz) }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Fee</div><div class="fw-semibold">{{ $booking->feeLabel() }}</div><div class="small text-muted">{{ $booking->flow === \App\Enums\BookingFlow::Direct ? 'Paid directly to the practitioner' : 'Paid securely through '.config('app.name') }}</div></div>
            </div>
        </div>

        @if ($s === \App\Enums\BookingStatus::Requested)
            <div class="alert alert-info d-flex gap-2"><x-icon name="clock" class="mt-1" /><div>Waiting for {{ $doc->user->firstName() }} to accept. They have until {{ $booking->respond_by?->setTimezone($tz)->format('D j M, g:i A') }}. Nothing is charged until then.</div></div>
        @elseif ($booking->needsPayment())
            <div class="card-soft p-4 mb-4 border-2" style="border-color:#E2654A !important">
                <h2 class="h4 mb-1">Complete payment to confirm</h2>
                <p class="text-muted">{{ $doc->user->firstName() }} has accepted. Please pay by <strong class="text-ink">{{ $booking->payment_due_at->setTimezone($tz)->format('D j M, g:i A') }}</strong> or the slot will be released.</p>
                <form method="POST" action="{{ route('patient.bookings.pay', $booking) }}">
                    @csrf
                    <button class="btn btn-clay btn-lg"><x-icon name="lock" /> Pay {{ $booking->feeLabel() }} securely</button>
                </form>
                @if ($booking->latestPayment?->status === \App\Enums\PaymentStatus::Failed)
                    <p class="small text-danger mt-2 mb-0">Your last payment attempt didn't go through. You can try again.</p>
                @endif
            </div>
        @elseif ($s === \App\Enums\BookingStatus::Confirmed)
            <div class="card-soft p-4 mb-4 bg-sage border-0">
                <h2 class="h4 mb-2">You're all set</h2>
                @if ($booking->meeting_details)
                    <div class="small text-muted mb-1">How to join</div>
                    <p class="fw-semibold mb-3">{!! nl2br(e($booking->meeting_details)) !!}</p>
                @else
                    <p class="mb-3">{{ $doc->user->firstName() }} will add joining details here before your session. We'll email you too.</p>
                @endif
                <ul class="small mb-0 ps-3">
                    <li>Find a quiet, private space with a good connection.</li>
                    <li>Have any reports, prescriptions or medicines nearby.</li>
                    <li>Join a couple of minutes early.</li>
                </ul>
            </div>
        @elseif ($s === \App\Enums\BookingStatus::Completed && ! $booking->review && Route::has('patient.reviews.create'))
            <div class="card-soft p-4 mb-4">
                <h2 class="h5">How did it go?</h2>
                <p class="text-muted small">Your review helps other patients choose with confidence.</p>
                <a href="{{ route('patient.reviews.create', $booking) }}" class="btn btn-primary"><i class="ri-star-line"></i> Leave a review</a>
            </div>
        @endif

        @if ($booking->refund_due)
            <div class="alert alert-warning">A refund of {{ $booking->feeLabel() }} is due on this booking. Our team will process it to your original payment method within 5–10 working days.</div>
        @endif

        @if ($s->holdsSlot() && $booking->starts_at->isFuture())
            <details class="card-soft p-4">
                <summary class="fw-semibold">Cancel this booking</summary>
                <p class="small text-muted mt-2">
                    @if ($s === \App\Enums\BookingStatus::Confirmed && $booking->flow === \App\Enums\BookingFlow::PlatformPayment)
                        Cancelling at least {{ $freeCancelHours }} hours before the session gets a full refund. Later cancellations aren't refundable.
                        @if (now()->diffInHours($booking->starts_at, false) < $freeCancelHours)<strong class="text-danger">This booking is now inside that window.</strong>@endif
                    @else
                        Nothing has been charged, so there's no fee to cancel.
                    @endif
                </p>
                <form method="POST" action="{{ route('patient.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                    @csrf
                    <textarea name="reason" rows="2" class="form-control mb-2" placeholder="Reason (optional)"></textarea>
                    <button class="btn btn-outline-danger"><i class="ri-close-circle-line"></i> Cancel booking</button>
                </form>
            </details>
        @endif
    </div>

    <div class="col-xl-4">
        <div class="card-soft p-4">
            <h2 class="h6 mb-3">Timeline</h2>
            <ul class="timeline mb-0">
                @foreach ($booking->history as $h)
                    <li><strong>{{ $h->to_status->label() }}</strong><br><small class="text-muted">{{ $h->created_at->setTimezone($tz)->format('j M, g:i A') }}</small></li>
                @endforeach
            </ul>
        </div>
        @if (Route::has('tickets.create'))
            <a href="{{ route('tickets.create', ['booking' => $booking->reference]) }}" class="btn btn-paper w-100 mt-3"><x-icon name="flag" /> Report a problem</a>
        @endif
    </div>
</div>
@endsection
