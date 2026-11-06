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
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Ticket</th><th>From</th><th>Category</th><th>Booking</th><th>Last activity</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($tickets as $t)
            <tr onclick="location.href='{{ route('admin.tickets.show', $t) }}'" style="cursor:pointer">
                <td><div class="fw-semibold">@if ($t->priority === 'high')<span class="text-danger" title="High priority">●</span> @endif{{ $t->subject }}</div><div class="small text-muted font-monospace">{{ $t->reference }}</div></td>
                <td>{{ $t->user->name }} <span class="small text-muted text-capitalize">({{ $t->user->role->value }})</span></td>
                <td class="small">{{ $t->category->label() }}</td>
                <td class="small font-monospace">{{ $t->booking?->reference ?? '—' }}</td>
                <td class="small">{{ $t->last_reply_at?->diffForHumans() }}</td>
                <td><x-status :status="$t->status" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No tickets here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tickets->links() }}
@endsection
