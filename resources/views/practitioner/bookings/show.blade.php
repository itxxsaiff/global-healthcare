@extends('layouts.dashboard')
@section('title', 'Booking '.$booking->reference)
@section('crumb', 'Booking '.$booking->reference)
@php
    $s = $booking->status;
    $patient = $booking->patient;
    $start = $booking->startsAtFor($tz);
@endphp
@section('content')
<a href="{{ route('practitioner.bookings.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> All bookings</a>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card-soft p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <div class="d-flex gap-3 align-items-center">
                    <x-avatar :name="$patient->name" size="lg" round />
                    <div>
                        <h1 class="h3 mb-0">{{ $patient->name }}</h1>
                        <div class="small text-muted">
                            {{ collect([$patient->patientProfile?->gender?->label(), $patient->patientProfile?->age() ? $patient->patientProfile->age().' years' : null])->filter()->implode(' · ') }}
                            · Ref {{ $booking->reference }}
                        </div>
                    </div>
                </div>
                <x-status :status="$s" />
            </div>
            <div class="row g-3 border-top pt-3">
                <div class="col-sm-4"><div class="small text-muted">Your time</div><div class="fw-semibold">{{ $start->format('D j M, g:i A') }}</div></div>
                <div class="col-sm-4"><div class="small text-muted">Patient's time</div><div class="fw-semibold">{{ $booking->startsAtFor($booking->patient_timezone)->format('D j M, g:i A') }}</div><div class="small text-muted">{{ str_replace('_', ' ', $booking->patient_timezone) }}</div></div>
                <div class="col-sm-4"><div class="small text-muted">Fee</div><div class="fw-semibold">{{ $booking->feeLabel() }}</div>
                    @if ($booking->commission_bps)<div class="small text-muted">You receive {{ \App\Support\Money::format($booking->practitioner_earning_minor, $booking->currency) }}</div>@else<div class="small text-muted">Direct payment, 0% commission</div>@endif
                </div>
            </div>
            @if ($booking->patient_note)
                <div class="border-top pt-3 mt-3"><div class="small text-muted mb-1">Patient's note (private)</div><p class="mb-0" style="white-space: pre-line">{{ $booking->patient_note }}</p></div>
            @endif
        </div>

        @if ($s === \App\Enums\BookingStatus::Requested)
            <div class="card-soft p-4 mb-4">
                <h2 class="h5">Respond to this request</h2>
                <p class="small text-muted">Reply by {{ $booking->respond_by?->setTimezone($tz)->format('D j M, g:i A') }}. After that the request expires automatically.</p>
                <form method="POST" action="{{ route('practitioner.bookings.accept', $booking) }}" class="mb-3">
                    @csrf
                    <label class="form-label small" for="meeting_details">How will you meet? <span class="text-muted fw-normal">(you can add this later)</span></label>
                    <textarea id="meeting_details" name="meeting_details" rows="2" class="form-control mb-2" placeholder="e.g. Google Meet link, or 'I'll WhatsApp video call you on your registered number'"></textarea>
                    <button class="btn btn-primary"><x-icon name="check" /> Accept{{ $booking->flow === \App\Enums\BookingFlow::PlatformPayment ? ' & send payment link' : ' & confirm' }}</button>
                </form>
                <details>
                    <summary class="small text-danger">Decline instead</summary>
                    <form method="POST" action="{{ route('practitioner.bookings.reject', $booking) }}" class="mt-2">
                        @csrf
                        <textarea name="reason" rows="2" class="form-control mb-2" placeholder="Optional note for the patient, e.g. suggest another day"></textarea>
                        <button class="btn btn-outline-danger btn-sm"><i class="ri-close-line"></i> Decline request</button>
                    </form>
                </details>
            </div>
        @elseif ($s->holdsSlot())
            <div class="card-soft p-4 mb-4">
                @if ($s === \App\Enums\BookingStatus::AwaitingPayment)
                    <div class="alert alert-warning small">Waiting for the patient to pay (due {{ $booking->payment_due_at?->setTimezone($tz)->format('D j M, g:i A') }}). You'll be notified the moment it's paid.</div>
                @endif
                <h2 class="h5">Joining details</h2>
                <form method="POST" action="{{ route('practitioner.bookings.meeting', $booking) }}">
                    @csrf
                    <textarea name="meeting_details" rows="2" class="form-control mb-2" placeholder="Meeting link or instructions">{{ $booking->meeting_details }}</textarea>
                    <button class="btn btn-paper btn-sm"><i class="ri-save-3-line"></i> Save details</button>
                </form>
                <hr>
                <div class="d-flex flex-wrap gap-2">
                    @if ($s === \App\Enums\BookingStatus::Confirmed && $booking->starts_at->isPast())
                        <form method="POST" action="{{ route('practitioner.bookings.complete', $booking) }}">@csrf<button class="btn btn-primary btn-sm"><x-icon name="check-circle" /> Mark as completed</button></form>
                    @endif
                    <details>
                        <summary class="small text-danger">Cancel booking</summary>
                        <form method="POST" action="{{ route('practitioner.bookings.cancel', $booking) }}" class="mt-2">
                            @csrf
                            <textarea name="reason" rows="2" class="form-control mb-2" required placeholder="Reason (shared with the patient)"></textarea>
                            <button class="btn btn-outline-danger btn-sm"><i class="ri-close-circle-line"></i> Cancel{{ $booking->latestPayment?->status === \App\Enums\PaymentStatus::Paid ? ' and refund patient' : '' }}</button>
                        </form>
                    </details>
                </div>
            </div>
        @endif
    </div>
    <div class="col-xl-4">
        <div class="card-soft p-4">
            <h2 class="h6 mb-3">Timeline</h2>
            <ul class="timeline mb-0">
                @foreach ($booking->history as $h)
                    <li><strong>{{ $h->to_status->label() }}</strong>@if ($h->actor) · {{ $h->actor->name }}@endif<br><small class="text-muted">{{ $h->created_at->setTimezone($tz)->format('j M, g:i A') }}</small>@if ($h->note)<br><small>{{ $h->note }}</small>@endif</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
