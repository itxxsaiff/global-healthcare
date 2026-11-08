@extends('layouts.dashboard')
@section('title', 'Payments')
@section('crumb', 'Payments')
@section('content')
<h1 class="dash-title">Payments</h1>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.payments.index') }}" class="chip {{ ! $status ? 'active' : '' }}">All</a>
    @foreach (\App\Enums\PaymentStatus::cases() as $s)<a href="{{ route('admin.payments.index', ['status' => $s->value]) }}" class="chip {{ $status === $s ? 'active' : '' }}">{{ ucfirst($s->value) }}</a>@endforeach
</div>
@if ($failedEvents->isNotEmpty())
    <div class="alert alert-warning small"><strong>Gateway events needing attention:</strong>
        @foreach ($failedEvents as $e)<div>{{ $e->created_at->format('j M H:i') }} · {{ $e->gateway }} · {{ $e->type }} · {{ $e->error }}</div>@endforeach
    </div>
@endif
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Date</th><th>Payer</th><th>For</th><th>Gateway</th><th>Amount</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @forelse ($payments as $p)
            <tr>
                <td class="small text-nowrap">{{ $p->created_at->format('j M Y, H:i') }}</td>
                <td><div class="cell-user"><x-avatar :name="$p->user->name" size="xs" round /><span class="name">{{ $p->user->name }}</span></div></td>
                <td class="small">{{ $p->payable instanceof \App\Models\Booking ? 'Booking '.$p->payable->reference : 'Membership' }}</td>
                <td><span class="ref">{{ $p->gateway }}</span></td>
                <td class="fw-semibold text-ink text-nowrap">{{ $p->amountLabel() }}</td>
                <td><span class="status tone-{{ $p->status->tone() }}">{{ ucfirst($p->status->value) }}</span></td>
                <td class="col-actions"><div class="actions">@if ($p->payable instanceof \App\Models\Booking)<x-act icon="eye-line" label="View booking" :href="route('admin.bookings.show', $p->payable)" />@else<span class="text-muted small">—</span>@endif</div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="7"><i class="ri-bank-card-line"></i>No payments yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $payments->links() }}
@endsection
