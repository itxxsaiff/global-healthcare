@props(['value' => 0, 'count' => null, 'showStars' => true])
@if ($count === 0)
    <span {{ $attributes->merge(['class' => 'rating text-muted fw-medium']) }}><small>New on {{ config('app.name') }}</small></span>
@else
<span {{ $attributes->merge(['class' => 'rating']) }} aria-label="Rated {{ number_format($value, 1) }} out of 5">
    @if ($showStars)
        <span class="stars">
            @for ($i = 1; $i <= 5; $i++)
                <svg class="icon {{ $i <= round($value) ? '' : 'off' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            @endfor
        </span>
    @endif
    {{ number_format($value, 1) }}
    @if ($count !== null)<small>({{ $count }})</small>@endif
</span>
@endif
