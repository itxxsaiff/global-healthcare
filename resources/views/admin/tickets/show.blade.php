@extends('layouts.dashboard')
@section('title', $ticket->subject)
@section('crumb', 'Ticket '.$ticket->reference)
@section('content')
<a href="{{ route('admin.tickets.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Tickets</a>
<div class="row g-4">
    <div class="col-xl-8">
        <h1 class="h3 mb-1">{{ $ticket->subject }}</h1>
        <div class="small text-muted mb-4">{{ $ticket->category->label() }} · {{ $ticket->reference }} · {{ $ticket->priority }} priority</div>
        @include('tickets._thread', ['messages' => $ticket->messages])
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="card-soft p-3 mt-4">
            @csrf
            <textarea name="body" rows="4" class="form-control mb-2" placeholder="Reply to {{ $ticket->user->firstName() }}…" required></textarea>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select name="status" class="form-select form-select-sm" style="max-width: 12rem">
                    @foreach (\App\Enums\TicketStatus::cases() as $s)<option value="{{ $s->value }}" @selected($s === \App\Enums\TicketStatus::InProgress)>Set: {{ $s->label() }}</option>@endforeach
                </select>
                <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="internal" value="1" id="internal"><label class="form-check-label small" for="internal">Internal note (not sent)</label></div>
                <button class="btn btn-primary ms-auto"><x-icon name="send" /> Send</button>
            </div>
        </form>
    </div>
    <div class="col-xl-4">
        <div class="card-soft p-4 mb-3 small">
            <h2 class="h6">From</h2>
            <div class="fw-semibold">{{ $ticket->user->name }}</div>
            <div class="text-muted">{{ $ticket->user->email }} · <span class="text-capitalize">{{ $ticket->user->role->value }}</span></div>
        </div>
        @if ($b = $ticket->booking)
            <div class="card-soft p-4 small">
                <h2 class="h6">Booking {{ $b->reference }}</h2>
                <div>{{ $b->patient->name }} with {{ $b->practitioner->displayName() }}</div>
                <div class="text-muted">{{ $b->starts_at->format('j M Y, H:i') }} UTC · {{ $b->feeLabel() }}</div>
                <div class="mt-2"><x-status :status="$b->status" /> @if ($b->refund_due)<span class="status tone-danger">Refund due</span>@endif</div>
                @if (Route::has('admin.bookings.show'))<a href="{{ route('admin.bookings.show', $b) }}" class="btn btn-sm btn-paper mt-3">Open booking</a>@endif
            </div>
        @endif
    </div>
</div>
@endsection
