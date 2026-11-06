{{-- Booking panel. The slot picker is added with the booking module. --}}
<div class="card-soft p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="font-serif fs-3 fw-semibold lh-1">{{ $profile->feeLabel() }}</div>
            <small class="text-muted">per {{ $profile->session_minutes }}-minute online session</small>
        </div>
        <span class="status tone-success">Taking patients</span>
    </div>
    @if ($profile->availabilityRules->isNotEmpty())
        <p class="small text-muted mb-3"><x-icon name="calendar" size="sm" /> Usually available
            {{ $profile->availabilityRules->pluck('weekday')->unique()->map(fn ($d) => substr(\App\Models\AvailabilityRule::DAYS[$d], 0, 3))->implode(', ') }}</p>
    @endif
    @if (Route::has('booking.create'))
        <a href="{{ route('booking.create', $profile->slug) }}" class="btn btn-primary btn-lg w-100">Choose a time</a>
    @else
        <button class="btn btn-primary btn-lg w-100" disabled>Online booking opening soon</button>
    @endif
</div>
