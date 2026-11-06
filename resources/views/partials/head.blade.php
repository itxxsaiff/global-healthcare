@php
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.config('app.name') : config('app.name').': Find verified doctors & therapists online';
    $description = trim($__env->yieldContent('description')) ?: 'Book online consultations with verified doctors, psychologists, dietitians and therapists. Search by specialty, symptom, gender and language.';
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
@hasSection('noindex')<meta name="robots" content="noindex, nofollow">@endif
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $pageTitle ?: config('app.name') }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="@yield('og_type', 'website')">
@hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- PWA --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#1F5C4A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<link rel="icon" href="/icons/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icons/icon-192.png">

@vite(['resources/scss/app.scss', 'resources/js/app.js'])
@stack('head')
