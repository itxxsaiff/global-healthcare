@props(['profile'])
@php
    $specialty = $profile->primarySpecialty;
@endphp
<article class="practitioner-card">
    <div class="pc-head">
        <x-avatar :profile="$profile" size="card" />
        <div class="min-w-0 flex-grow-1">
            <h3 class="pc-name">
                <a href="{{ route('practitioners.show', $profile) }}">{{ $profile->displayName() }}</a>
                <span class="verified-tick" data-bs-toggle="tooltip" title="Identity & licence verified"><x-icon name="badge-check" /></span>
            </h3>
            <div class="pc-role"><strong>{{ $specialty?->practitioner_label }}</strong>@if($profile->years_experience) · {{ $profile->years_experience }} yrs exp.@endif</div>
            <div class="mt-1"><x-rating :value="$profile->rating_avg" :count="$profile->reviews_count" /></div>
            <div class="pc-meta">
                @if ($profile->locationLabel())<span><x-icon name="map-pin" size="sm" /> {{ $profile->locationLabel() }}</span>@endif
                @if ($profile->relationLoaded('languages') && $profile->languages->isNotEmpty())
                    <span><x-icon name="languages" size="sm" /> {{ $profile->languages->pluck('name')->take(3)->implode(', ') }}</span>
                @endif
            </div>
        </div>
    </div>

    @if ($profile->headline)
        <p class="pc-headline line-clamp-2">{{ $profile->headline }}</p>
    @endif

    @if ($profile->relationLoaded('conditions') && $profile->conditions->isNotEmpty())
        <div class="pc-tags">
            @foreach ($profile->conditions->take(3) as $condition)
                <span>{{ $condition->name }}</span>
            @endforeach
        </div>
    @endif

    <div class="pc-foot">
        <div class="pc-fee">
            <span class="icon-tile text-forest"><x-icon name="video" size="sm" /></span>
            <span><small>Video consultation</small><strong>{{ $profile->feeLabel() }}</strong> <small class="d-inline">/ {{ $profile->session_minutes }} min</small></span>
        </div>
        <a href="{{ route('practitioners.show', $profile) }}#book" class="btn btn-primary btn-sm">Book consultation</a>
    </div>
</article>
