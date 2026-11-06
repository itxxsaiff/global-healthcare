<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
<div class="auth-shell">
    <div class="auth-form">
        <div class="d-flex justify-content-between align-items-center">
            <x-logo />
            <a href="{{ route('home') }}" class="small text-muted d-inline-flex align-items-center gap-1"><x-icon name="arrow-left" size="sm" /> Back to site</a>
        </div>
        <div class="auth-form-inner">
            <x-flash />
            @yield('content')
        </div>
        <p class="small text-muted mb-0">Protected by encryption. We never share your health information.</p>
    </div>
    <aside class="auth-side">
        <div>
            <span class="eyebrow" style="color:#E9B79E">@yield('side_eyebrow', 'Why people choose us')</span>
        </div>
        <div>
            @hasSection('side')
                @yield('side')
            @else
                <blockquote class="mb-4">"I'd put off seeing someone for months. Being able to choose a female doctor who speaks Urdu, from my own sofa, made all the difference."</blockquote>
                <p class="mb-0">A patient from Lahore, after a dermatology consultation</p>
            @endif
        </div>
        <div class="row g-3">
            <div class="col-4"><div class="auth-side-card"><strong class="d-block font-serif fs-3 text-white">14</strong><small>specialties, all non-emergency</small></div></div>
            <div class="col-4"><div class="auth-side-card"><strong class="d-block font-serif fs-3 text-white">100%</strong><small>licence-checked practitioners</small></div></div>
            <div class="col-4"><div class="auth-side-card"><strong class="d-block font-serif fs-3 text-white">9+</strong><small>countries, your local time zone</small></div></div>
        </div>
    </aside>
</div>
@include('partials.pwa')
</body>
</html>
