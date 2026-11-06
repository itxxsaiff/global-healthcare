@extends('layouts.dashboard')
@section('title', $ticket->subject)
@section('crumb', 'Support · '.$ticket->reference)
@section('content')
<a href="{{ route('tickets.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Support</a>
<div class="mx-auto" style="max-width: 760px">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $ticket->subject }}</h1>
            <div class="small text-muted">{{ $ticket->category->label() }} · {{ $ticket->reference }}@if ($ticket->booking) · booking {{ $ticket->booking->reference }}@endif</div>
        </div>
        <x-status :status="$ticket->status" />
    </div>
    @include('tickets._thread', ['messages' => $ticket->messages])
    @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
        <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="card-soft p-3 mt-4">
            @csrf
            <textarea name="body" rows="3" class="form-control mb-2" placeholder="Write a reply…" required></textarea>
            <div class="d-flex justify-content-between gap-2">
                <input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png" class="form-control form-control-sm" style="max-width: 18rem">
                <button class="btn btn-primary"><x-icon name="send" /> Send</button>
            </div>
        </form>
    @endif
</div>
@endsection
