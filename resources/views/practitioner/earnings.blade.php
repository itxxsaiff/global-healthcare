@extends('layouts.dashboard')
@section('title', 'Earnings')
@section('crumb', 'Earnings')
@section('content')
<h1 class="dash-title">Earnings</h1>
<p class="text-muted mb-4">For bookings paid through the platform (Starter plan). Your share is paid out by our team; contact support for payout details.</p>
<div class="row g-3 mb-4">
    @forelse ($balance as $currency => $amount)
        <div class="col-sm-6 col-xl-3"><x-stat icon="wallet" tone="teal" label="Balance owed to you" :value="\App\Support\Money::format($amount, $currency)" /></div>
    @empty
        <div class="col-12"><div class="alert alert-light border mb-0">No platform-paid bookings yet. On a paid plan, patients pay you directly and nothing passes through here.</div></div>
    @endforelse
</div>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Date</th><th>Booking</th><th>Entry</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            @continue($e->type === \App\Enums\LedgerType::CommissionEarned)
            <tr><td class="small">{{ $e->created_at->format('j M Y') }}</td><td>@if ($e->booking)<span class="ref">{{ $e->booking->reference }}</span>@else — @endif</td><td class="small">{{ $e->note }}</td><td class="text-end {{ $e->amount_minor < 0 ? 'text-danger' : '' }}">{{ $e->amountLabel() }}</td></tr>
        @empty
            <tr class="empty-row"><td colspan="4"><i class="ri-wallet-3-line"></i>Nothing yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $entries->links() }}
@endsection
