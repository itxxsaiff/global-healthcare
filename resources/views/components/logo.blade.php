@props(['href' => null, 'tagline' => true])
<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'brand']) }} aria-label="{{ config('app.name') }} home">
    {{-- Mark: a leaf folded into a medical cross. Hand-built, two colours. --}}
    <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true">
        <rect x="1" y="1" width="38" height="38" rx="11" fill="#1F5C4A"/>
        <path d="M17 9.5h6v7.5h7.5v6H23v7.5h-6V23H9.5v-6H17z" fill="#FAF7F2"/>
        <path d="M24.5 30.5c0-3.6 2.4-6 6-6 0 3.6-2.4 6-6 6z" fill="#C4623A"/>
    </svg>
    <span>
        {{ config('app.name') }}
        @if ($tagline)<small>Verified online care</small>@endif
    </span>
</a>
