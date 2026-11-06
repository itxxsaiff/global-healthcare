<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="d-flex flex-column min-vh-100">
    @include('partials.header')

    <main id="main" class="flex-grow-1">
        @hasSection('flash')
            @yield('flash')
        @elseif (session()->hasAny(['success', 'error', 'warning', 'info', 'status']))
            <div class="container pt-3"><x-flash /></div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
    @unless (View::hasSection('hide_whatsapp'))
        @include('partials.whatsapp-widget')
    @endunless

    @stack('schema')
    @stack('scripts')
    @include('partials.pwa')
</body>
</html>
