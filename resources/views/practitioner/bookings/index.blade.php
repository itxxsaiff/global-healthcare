@extends('layouts.dashboard')
@section('title', 'Bookings')
@section('crumb', 'Bookings')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Bookings</h1>
        <p class="text-muted mb-0">Times shown in your time zone ({{ str_replace('_', ' ', $profile->timezone) }}).</p>
    </div>
    <a href="{{ route('practitioner.availability.edit') }}" class="btn btn-paper"><x-icon name="clock" /> Availability</a>
</div>
<div class="d-flex gap-2 mb-3">
    @foreach (['requests' => 'Requests', 'upcoming' => 'Upcoming', 'past' => 'Past'] as $key => $label)
        <a href="{{ route('practitioner.bookings.index', ['tab' => $key]) }}" class="chip {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    <a href="#calendar" class="chip ms-auto"><x-icon name="calendar" size="sm" /> Calendar</a>
</div>
@forelse ($bookings as $b)
    <a href="{{ route('practitioner.bookings.show', $b) }}" class="card-soft card-hover d-flex flex-wrap gap-3 align-items-center p-3 mb-2 text-reset text-decoration-none">
        <x-avatar :name="$b->patient->name" size="md" round />
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $b->patient->name }}</div>
            <div class="small text-muted">{{ $b->startsAtFor($profile->timezone)->format('D j M, g:i A') }} · {{ $b->reference }}</div>
        </div>
        <div class="text-end">
            <x-status :status="$b->status" />
            @if ($b->status === \App\Enums\BookingStatus::Requested)<div class="small text-warning mt-1">Reply by {{ $b->respond_by?->setTimezone($profile->timezone)->format('D g:i A') }}</div>@endif
        </div>
    </a>
@empty
    <x-empty icon="inbox" title="Nothing here" text="{{ $tab === 'requests' ? 'New booking requests will appear here. We also email you instantly.' : 'No bookings in this list.' }}" />
@endforelse
<div class="mt-3">{{ $bookings->links() }}</div>

<div class="card-soft p-3 p-md-4 mt-4" id="calendar">
    <div id="fc" data-feed="{{ route('practitioner.bookings.calendar') }}" data-timezone="{{ $profile->timezone }}" style="min-height: 560px"></div>
</div>
@endsection
@push('scripts')
@vite('resources/js/calendar.js')
@endpush
