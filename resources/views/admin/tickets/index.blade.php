@extends('layouts.dashboard')
@section('title', 'Support tickets')
@section('crumb', 'Support tickets')
@section('content')
<h1 class="dash-title">Support tickets</h1>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.tickets.index') }}" class="chip {{ ! $status ? 'active' : '' }}">Needs attention</a>
    @foreach (\App\Enums\TicketStatus::cases() as $s)
        <a href="{{ route('admin.tickets.index', ['status' => $s->value]) }}" class="chip {{ $status === $s ? 'active' : '' }}">{{ $s->label() }}</a>
    @endforeach
</div>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Ticket</th><th>From</th><th>Category</th><th>Booking</th><th>Last activity</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @forelse ($tickets as $t)
            <tr>
                <td><div class="fw-semibold">@if ($t->priority === 'high')<i class="ri-alarm-warning-fill text-danger" title="High priority"></i> @endif{{ $t->subject }}</div><span class="ref mt-1 d-inline-block">{{ $t->reference }}</span></td>
                <td><div class="cell-user"><x-avatar :name="$t->user->name" size="xs" round /><div><div class="name">{{ $t->user->name }}</div><div class="sub text-capitalize">{{ $t->user->role->value }}</div></div></div></td>
                <td class="small">{{ $t->category->label() }}</td>
                <td class="small font-monospace">{{ $t->booking?->reference ?? '—' }}</td>
                <td class="small">{{ $t->last_reply_at?->diffForHumans() }}</td>
                <td><x-status :status="$t->status" /></td>
                <td class="col-actions"><div class="actions"><x-act icon="chat-3-line" label="Open & reply" :href="route('admin.tickets.show', $t)" /></div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="7"><i class="ri-customer-service-2-line"></i>No tickets here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tickets->links() }}
@endsection
