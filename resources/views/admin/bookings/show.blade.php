@extends('layouts.dashboard')
@section('title', 'Booking '.$booking->reference)
@section('crumb', 'Booking '.$booking->reference)
@section('content')
<a href="{{ route('admin.bookings.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Bookings</a>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card-soft p-4 mb-4">
            <div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">{{ $booking->reference }}</h1><x-status :status="$booking->status" /></div>
            <dl class="row small mb-0">
                <dt class="col-sm-4 text-muted">Patient</dt><dd class="col-sm-8">{{ $booking->patient->name }} · {{ $booking->patient->email }}</dd>
                <dt class="col-sm-4 text-muted">Practitioner</dt><dd class="col-sm-8">{{ $booking->practitioner->displayName() }} · {{ $booking->practitioner->user->email }}</dd>
                <dt class="col-sm-4 text-muted">Session</dt><dd class="col-sm-8">{{ $booking->starts_at->format('D j M Y, H:i') }} UTC ({{ $booking->startsAtFor($booking->practitioner_timezone)->format('H:i') }} {{ $booking->practitioner_timezone }})</dd>
                <dt class="col-sm-4 text-muted">Flow</dt><dd class="col-sm-8">{{ $booking->flow === \App\Enums\BookingFlow::Direct ? 'Direct dealing (no platform payment)' : 'Paid through platform' }}</dd>
                <dt class="col-sm-4 text-muted">Fee / commission</dt><dd class="col-sm-8">{{ $booking->feeLabel() }} · {{ $booking->commission_bps / 100 }}% = {{ \App\Support\Money::format($booking->commission_minor, $booking->currency) }} · practitioner {{ \App\Support\Money::format($booking->practitioner_earning_minor, $booking->currency) }}</dd>
                @if ($booking->cancellation_reason)<dt class="col-sm-4 text-muted">Reason</dt><dd class="col-sm-8">{{ $booking->cancellation_reason }}</dd>@endif
            </dl>
        </div>
        <div class="card-soft p-4">
            <h2 class="h6">Payments</h2>
            @forelse ($booking->payments as $p)
                <div class="d-flex justify-content-between small py-2 border-bottom"><span class="font-monospace">{{ $p->gateway === 'manual' ? 'manual · '.($p->meta['method'] ?? '').(($p->meta['reference'] ?? null) ? ' · '.$p->meta['reference'] : '') : $p->gateway.' · '.($p->gateway_reference ?? $p->uuid) }}</span><span>{{ $p->amountLabel() }} · <span class="status tone-{{ $p->status->tone() }}">{{ ucfirst($p->status->value) }}</span></span></div>
            @empty
                <p class="small text-muted mb-0">No payments.</p>
            @endforelse
        </div>
    </div>
    <div class="col-xl-4">
        @if ($booking->refund_due)
            <div class="card-soft p-4 mb-3 border-danger">
                <h2 class="h6 text-danger">Refund due: {{ $booking->feeLabel() }}</h2>
                <p class="small text-muted">Refund it in the payment gateway's merchant portal, then record it here. This reverses the commission ledger.</p>
                <form method="POST" action="{{ route('admin.bookings.refunded', $booking) }}">@csrf
                    <input name="note" class="form-control form-control-sm mb-2" placeholder="Gateway refund reference">
                    <button class="btn btn-sm btn-primary w-100"><i class="ri-refund-2-line"></i> Mark as refunded</button>
                </form>
            </div>
        @endif
        @if ($booking->flow === \App\Enums\BookingFlow::PlatformPayment && in_array($booking->status, [\App\Enums\BookingStatus::AwaitingPayment, \App\Enums\BookingStatus::PaymentExpired], true))
            <div class="card-soft p-4 mb-3">
                <h2 class="h6 mb-1">Record a manual payment</h2>
                <p class="small text-muted">Patient paid outside the website (bank transfer, JazzCash, Easypaisa)? Record it here: the booking is confirmed and the commission split is recorded automatically.</p>
                <div class="small rounded-3 bg-sage p-2 px-3 mb-2">
                    <div class="d-flex justify-content-between"><span>Fee</span><strong class="text-ink">{{ $booking->feeLabel() }}</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>Platform ({{ $booking->commission_bps / 100 }}%)</span><span>{{ \App\Support\Money::format($booking->commission_minor, $booking->currency) }}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Doctor ({{ 100 - $booking->commission_bps / 100 }}%)</span><span>{{ \App\Support\Money::format($booking->practitioner_earning_minor, $booking->currency) }}</span></div>
                </div>
                <form method="POST" action="{{ route('admin.bookings.manual-payment', $booking) }}">@csrf
                    <select name="method" class="form-select form-select-sm mb-2" required>
                        <option value="">Paid by…</option>
                        @foreach (['Bank transfer', 'JazzCash', 'Easypaisa', 'Cash', 'Other'] as $m)<option @selected(old('method') === $m)>{{ $m }}</option>@endforeach
                    </select>
                    <input name="reference" value="{{ old('reference') }}" class="form-control form-control-sm mb-2" placeholder="Transaction ID (optional)">
                    <div class="form-check small mb-2">
                        <input class="form-check-input" type="checkbox" name="confirm_amount" id="confirm_amount" value="1" required>
                        <label class="form-check-label" for="confirm_amount">I confirm the full {{ $booking->feeLabel() }} was received.</label>
                    </div>
                    <x-field-error name="confirm_amount" />
                    <button class="btn btn-sm btn-primary w-100"><i class="ri-hand-coin-line"></i> Mark as paid</button>
                </form>
            </div>
        @endif
        @if ($booking->status->holdsSlot())
            <div class="card-soft p-4 mb-3">
                <h2 class="h6">Cancel on behalf of the platform</h2>
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">@csrf
                    <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" required placeholder="Reason (sent to both sides)"></textarea>
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="ri-close-circle-line"></i> Cancel booking</button>
                </form>
            </div>
        @endif
        <div class="card-soft p-4">
            <h2 class="h6 mb-3">History</h2>
            <ul class="timeline mb-0">
                @foreach ($booking->history as $h)
                    <li><strong>{{ $h->to_status->label() }}</strong> · {{ $h->actor?->name ?? 'System' }}<br><small class="text-muted">{{ $h->created_at->format('j M, H:i') }} UTC</small>@if ($h->note)<br><small>{{ $h->note }}</small>@endif</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
