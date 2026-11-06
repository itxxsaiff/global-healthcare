@extends('layouts.dashboard')
@section('title', 'Support')
@section('crumb', 'Support')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Support</h1>
        <p class="text-muted mb-0">Report a no-show, a payment problem, a technical issue or unprofessional behaviour.</p>
    </div>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary"><x-icon name="plus" /> Report an issue</a>
</div>
@forelse ($tickets as $t)
    <a href="{{ route('tickets.show', $t) }}" class="card-soft card-hover p-3 mb-2 d-flex flex-wrap gap-3 align-items-center text-reset text-decoration-none">
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $t->subject }}</div>
            <div class="small text-muted">{{ $t->category->label() }} · {{ $t->reference }} · updated {{ $t->updated_at->diffForHumans() }}</div>
        </div>
        <x-status :status="$t->status" />
    </a>
@empty
    <x-empty icon="life-buoy" title="No support requests" text="If something goes wrong with a booking or payment, let us know here and we'll sort it out." />
@endforelse
{{ $tickets->links() }}
@endsection
