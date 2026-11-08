@extends('layouts.dashboard')
@section('title', 'Commission wallet')
@section('crumb', 'Commission wallet')
@php $label = fn ($t) => ['commission_earned' => 'Commission earned', 'practitioner_payable' => 'Owed to practitioners', 'payout' => 'Paid out', 'refund_reversal' => 'Refunded'][$t] ?? $t; @endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Commission wallet</h1>
        <p class="text-muted mb-0">Plan 1 bookings: the platform's 20% and what each practitioner is owed.</p>
    </div>
    <form class="table-toolbar mb-0">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
        <span class="text-muted">to</span>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
        <button class="btn btn-sm btn-primary"><i class="ri-calendar-check-line"></i> Apply</button>
    </form>
</div>
@forelse ($totals as $currency => $rows)
    <div class="row g-3 mb-4">
        @foreach ($rows as $row)
            @php $ico = ['commission_earned' => ['trending-up', 'teal'], 'practitioner_payable' => ['users', 'amber'], 'payout' => ['banknote', 'sky'], 'refund_reversal' => ['refresh', 'coral']][$row->type->value] ?? ['wallet', 'teal']; @endphp
            <div class="col-6 col-xl-3"><x-stat :icon="$ico[0]" :tone="$ico[1]" :label="$label($row->type->value).' · '.$currency" :value="\App\Support\Money::format(abs((int) $row->total), $currency)" /></div>
        @endforeach
    </div>
@empty
    <div class="alert alert-light border">No ledger activity in this period yet.</div>
@endforelse

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Owed to practitioners</h2>
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead><tr><th>Practitioner</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                    @forelse ($owed as $o)
                        <tr><td><div class="cell-user"><x-avatar :name="$o->name" size="xs" round /><span class="name">{{ $o->name }}</span></div></td><td class="text-end fw-semibold text-ink">{{ \App\Support\Money::format((int) $o->balance, $o->currency) }}</td></tr>
                    @empty
                        <tr class="empty-row"><td colspan="2"><i class="ri-checkbox-circle-line"></i>Nothing owed.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $owed->links() }}</div>
        </div>
    </div>
    <div class="col-xl-5">
        <form method="POST" action="{{ route('admin.wallet.payouts.store') }}" class="card-soft p-4 h-100">
            @csrf
            <h2 class="h5 mb-3">Record a payout</h2>
            <select name="practitioner_profile_id" class="form-select mb-2" required>
                <option value="">Practitioner…</option>
                @foreach ($payees as $o)<option value="{{ $o->practitioner_profile_id }}">{{ $o->name }}</option>@endforeach
            </select>
            <div class="row g-2 mb-2">
                <div class="col-4"><select name="currency" class="form-select"><option>PKR</option><option>USD</option></select></div>
                <div class="col-8"><input type="number" step="1" min="1" name="amount" class="form-control" placeholder="Amount" required></div>
            </div>
            <input name="method" class="form-control mb-2" placeholder="Method (bank transfer, JazzCash…)" required>
            <input name="reference" class="form-control mb-2" placeholder="Transaction reference">
            <input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="form-control mb-3" required>
            <button class="btn btn-primary w-100"><i class="ri-hand-coin-line"></i> Record payout</button>
        </form>
    </div>
</div>

<h2 class="h5 mt-5 mb-3">Recent ledger entries</h2>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>When</th><th>Practitioner</th><th>Type</th><th>Note</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        @foreach ($recent as $e)
            <tr><td class="small text-nowrap">{{ $e->created_at->format('j M, H:i') }}</td><td><span class="fw-semibold">{{ $e->practitioner->user->name }}</span></td><td class="small">{{ $label($e->type->value) }}</td><td class="small text-muted">{{ $e->note }}</td><td class="text-end {{ $e->amount_minor < 0 ? 'text-danger' : '' }}">{{ $e->amountLabel() }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $recent->links() }}
@endsection
