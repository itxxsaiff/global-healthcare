@extends('layouts.dashboard')
@section('title', 'Earnings')
@section('crumb', 'Earnings')
@section('content')
@php $money = fn ($v, $c) => \App\Support\Money::format((int) $v, $c); @endphp
<h1 class="dash-title">Earnings</h1>
<p class="text-muted mb-4">On the Starter plan there is no membership fee. When a patient pays for a consultation through {{ config('app.name') }}, the platform keeps its commission and the rest is yours. Our team transfers your balance to you; contact support for payout details.</p>

@forelse ($summary as $currency => $sum)
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat icon="wallet" tone="teal" label="Balance owed to you" :value="$money($balance[$currency] ?? 0, $currency)" note="waiting to be paid out" /></div>
        <div class="col-6 col-xl-3"><x-stat icon="trending-up" tone="leaf" label="Your earnings" :value="$money($sum['share'], $currency)" note="your share, after refunds" /></div>
        <div class="col-6 col-xl-3"><x-stat icon="credit-card" tone="sky" label="Platform commission" :value="$money($sum['commission'], $currency)" :note="'of '.$money($sum['fees'], $currency).' fees paid'" /></div>
        <div class="col-6 col-xl-3"><x-stat icon="banknote" tone="amber" label="Paid out to you" :value="$money($sum['paid_out'], $currency)" note="transferred so far" /></div>
    </div>
@empty
    <div class="alert alert-light border mb-4">No platform-paid consultations yet. On a paid plan, patients pay you directly and nothing passes through here.</div>
@endforelse

<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Date</th><th>Booking</th><th>Entry</th><th class="text-end">Consultation fee</th><th class="text-end">Platform commission</th><th class="text-end">Your amount</th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            @php $b = $e->booking; $isShare = $e->type === \App\Enums\LedgerType::PractitionerPayable; @endphp
            <tr>
                <td class="small text-nowrap">{{ $e->created_at->format('j M Y') }}</td>
                <td>@if ($b)<span class="ref">{{ $b->reference }}</span>@else — @endif</td>
                <td class="small">
                    @switch($e->type)
                        @case(\App\Enums\LedgerType::PractitionerPayable) Your share ({{ $b ? 100 - $b->commission_bps / 100 : 80 }}%) @break
                        @case(\App\Enums\LedgerType::PayableReversal) <span class="text-danger">Refunded to patient</span> @break
                        @case(\App\Enums\LedgerType::Payout) Payout · {{ \Illuminate\Support\Str::after($e->note, 'Payout ') }} @break
                    @endswitch
                </td>
                <td class="text-end small text-muted">{{ $isShare && $b ? $money($b->fee_minor, $b->currency) : '' }}</td>
                <td class="text-end small text-muted">{{ $isShare && $b ? '−'.$money($b->commission_minor, $b->currency).' ('.($b->commission_bps / 100).'%)' : '' }}</td>
                <td class="text-end fw-semibold {{ $e->amount_minor < 0 ? 'text-danger' : 'text-ink' }}">{{ $e->amountLabel() }}</td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="6"><i class="ri-wallet-3-line"></i>Nothing yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $entries->links() }}
@endsection
