@extends('layouts.dashboard')
@section('title', 'My bookings')
@section('crumb', 'My bookings')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <h1 class="dash-title mb-0">My bookings</h1>
    <a href="{{ route('directory') }}" class="btn btn-primary"><x-icon name="plus" /> New booking</a>
</div>
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('patient.bookings.index') }}" class="chip {{ $tab === 'upcoming' ? 'active' : '' }}">Upcoming</a>
    <a href="{{ route('patient.bookings.index', ['tab' => 'past']) }}" class="chip {{ $tab === 'past' ? 'active' : '' }}">Past & cancelled</a>
</div>
@forelse ($bookings as $b)
    <a href="{{ route('patient.bookings.show', $b) }}" class="card-soft card-hover d-flex flex-wrap gap-3 align-items-center p-3 mb-2 text-reset text-decoration-none">
        <x-avatar :profile="$b->practitioner" size="md" />
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $b->practitioner->displayName() }}</div>
            <div class="small text-muted">{{ $b->practitioner->primarySpecialty?->practitioner_label }} · {{ $b->startsAtFor(auth()->user()->timezone)->format('D j M Y, g:i A') }}</div>
        </div>
        <div class="text-end">
            <x-status :status="$b->status" />
            <div class="small text-muted mt-1">{{ $b->feeLabel() }}</div>
        </div>
    </a>
@empty
    <x-empty icon="calendar" title="{{ $tab === 'upcoming' ? 'No upcoming bookings' : 'Nothing here yet' }}" text="When you book a consultation, it shows up here." />
@endforelse
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
