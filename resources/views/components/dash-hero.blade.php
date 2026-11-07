@props(['title', 'hello' => null])
@php
    $hello ??= 'Good '.(now(auth()->user()?->timezone ?: config('app.timezone'))->hour < 12 ? 'morning' : (now(auth()->user()?->timezone ?: config('app.timezone'))->hour < 17 ? 'afternoon' : 'evening')).',';
@endphp
<section {{ $attributes->class(['dash-hero']) }}>
    <svg class="dash-hero-art" viewBox="0 0 220 130" fill="none" aria-hidden="true">
        <circle cx="160" cy="70" r="58" fill="#fff" fill-opacity=".55"/>
        <circle cx="160" cy="70" r="40" stroke="currentColor" stroke-opacity=".18" stroke-width="10"/>
        <path d="M4 92h52l12-30 16 52 16-74 14 52h36" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M150 58h6v-8h8v8h6v8h-6v8h-8v-8h-6z" fill="currentColor" fill-opacity=".85"/>
    </svg>
    <div class="row g-3 align-items-center">
        <div class="col-lg">
            <div class="hello">{{ $hello }}</div>
            <h1>{{ $title }}</h1>
            @if (trim($slot))<p>{{ $slot }}</p>@endif
        </div>
        @isset($actions)
            <div class="col-lg-auto dash-hero-actions d-flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>
</section>
