@extends('layouts.dashboard')
@section('title', 'Bookings')
@section('crumb', 'Bookings')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <h1 class="dash-title mb-0">Bookings</h1>
    <form class="table-toolbar mb-0"><div class="search"><i class="ri-search-line"></i><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Reference or name"></div><button class="btn btn-sm btn-primary"><i class="ri-search-line"></i> Search</button></form>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.bookings.index') }}" class="chip {{ ! $status && ! request('refunds') ? 'active' : '' }}">All</a>
    <a href="{{ route('admin.bookings.index', ['refunds' => 1]) }}" class="chip {{ request('refunds') ? 'active' : '' }}">Refunds due @if ($refundCount)<strong class="text-danger">{{ $refundCount }}</strong>@endif</a>
    @foreach (\App\Enums\BookingStatus::cases() as $s)
        <a href="{{ route('admin.bookings.index', ['status' => $s->value]) }}" class="chip {{ $status === $s ? 'active' : '' }}">{{ $s->label() }}</a>
    @endforeach
</div>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Ref</th><th>Patient</th><th>Practitioner</th><th>Session (UTC)</th><th>Fee</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @forelse ($bookings as $b)
            <tr>
                <td><span class="ref">{{ $b->reference }}</span></td>
                <td><div class="cell-user"><x-avatar :name="$b->patient->name" size="xs" round /><span class="name">{{ $b->patient->name }}</span></div></td>
                <td><div class="cell-user"><x-avatar :profile="$b->practitioner" size="xs" round /><span class="name">{{ $b->practitioner->user->name }}</span></div></td>
                <td class="small text-nowrap">{{ $b->starts_at->format('j M Y, H:i') }}</td>
                <td class="small">{{ $b->feeLabel() }}@if ($b->commission_minor)<br><span class="text-muted">{{ \App\Support\Money::format($b->commission_minor, $b->currency) }} comm.</span>@endif</td>
                <td><x-status :status="$b->status" />@if ($b->refund_due)<br><span class="status tone-danger mt-1">Refund due</span>@endif</td>
                <td class="col-actions"><div class="actions"><x-act icon="eye-line" label="View booking" :href="route('admin.bookings.show', $b)" />@if ($b->refund_due)<x-act icon="refund-2-line" label="Record refund" tone="danger" :href="route('admin.bookings.show', $b)" />@endif</div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="7"><i class="ri-calendar-line"></i>No bookings found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $bookings->links() }}
@endsection
