@props(['profile'])
@php
    $specialty = $profile->primarySpecialty;
@endphp
<article class="practitioner-card">
    <div class="pc-head">
        <x-avatar :profile="$profile" />
        <div class="min-w-0">
            <h3 class="pc-name">
                <a href="{{ route('practitioners.show', $profile) }}">{{ $profile->displayName() }}</a>
                <span class="verified-tick" data-bs-toggle="tooltip" title="Identity & licence verified"><x-icon name="badge-check" /></span>
            </h3>
            <div class="pc-role">{{ $specialty?->practitioner_label }}@if($profile->years_experience) · {{ $profile->years_experience }} yrs exp.@endif</div>
            <div class="mt-1"><x-rating :value="$profile->rating_avg" :count="$profile->reviews_count" /></div>
        </div>
    </div>

    @if ($profile->headline)
        <p class="pc-headline line-clamp-2">{{ $profile->headline }}</p>
    @endif

    <div class="pc-meta">
        @if ($profile->locationLabel())<span><x-icon name="map-pin" size="sm" /> {{ $profile->locationLabel() }}</span>@endif
        @if ($profile->relationLoaded('languages') && $profile->languages->isNotEmpty())
            <span><x-icon name="languages" size="sm" /> {{ $profile->languages->pluck('name')->implode(', ') }}</span>
        @endif
    </div>

    @if ($profile->relationLoaded('conditions') && $profile->conditions->isNotEmpty())
        <div class="d-flex flex-wrap gap-1">
            @foreach ($profile->conditions->take(3) as $condition)
                <span class="chip">{{ $condition->name }}</span>
            @endforeach
        </div>
    @endif

    <div class="pc-foot">
        <div class="pc-fee">
            {{ $profile->feeLabel() }}
            <small>per {{ $profile->session_minutes }}-min session</small>
        </div>
        <a href="{{ route('practitioners.show', $profile) }}#book" class="btn btn-primary btn-sm position-relative" style="z-index:1">Book</a>
    </div>
</article>
