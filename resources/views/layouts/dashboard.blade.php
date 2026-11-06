@php
    $user = auth()->user();
    $menus = [
        'admin' => [
            'Overview' => [['admin.dashboard', 'dashboard', 'Dashboard']],
            'Practitioners' => [
                ['admin.verifications.index', 'shield-check', 'Verification queue', 'pendingVerifications'],
                ['admin.practitioners.index', 'stethoscope', 'All practitioners'],
                ['admin.plans.index', 'credit-card', 'Membership plans'],
            ],
            'Bookings & money' => [
                ['admin.bookings.index', 'calendar', 'Bookings'],
                ['admin.wallet.index', 'wallet', 'Commission wallet'],
                ['admin.payments.index', 'banknote', 'Payments'],
            ],
            'Moderation' => [
                ['admin.reviews.index', 'star', 'Reviews', 'pendingReviews'],
                ['admin.questions.index', 'help-circle', 'Questions', 'pendingQuestions'],
                ['admin.articles.index', 'book-open', 'Articles', 'pendingArticles'],
                ['admin.tags.index', 'tag', 'Custom tags', 'pendingTags'],
            ],
            'Platform' => [
                ['admin.tickets.index', 'life-buoy', 'Support tickets', 'openTickets'],
                ['admin.users.index', 'users', 'Users'],
                ['admin.taxonomy.index', 'list', 'Specialties & symptoms'],
                ['admin.pages.index', 'file-text', 'Legal pages'],
                ['admin.settings.edit', 'sliders', 'Settings'],
                ['admin.audit.index', 'eye', 'Audit log'],
            ],
        ],
        'practitioner' => [
            'Overview' => [['practitioner.dashboard', 'dashboard', 'Dashboard']],
            'Practice' => [
                ['practitioner.bookings.index', 'calendar', 'Bookings', 'pendingRequests'],
                ['practitioner.availability.edit', 'clock', 'Availability'],
                ['practitioner.earnings.index', 'wallet', 'Earnings'],
            ],
            'Profile' => [
                ['practitioner.onboarding.show', 'user', 'My profile'],
                ['practitioner.membership.show', 'credit-card', 'Membership'],
            ],
            'Community' => [
                ['practitioner.questions.index', 'help-circle', 'Answer questions'],
                ['practitioner.articles.index', 'book-open', 'My articles'],
            ],
            'Account' => [
                ['tickets.index', 'life-buoy', 'Support'],
                ['account.edit', 'sliders', 'Account & security'],
            ],
        ],
        'patient' => [
            'Overview' => [['patient.dashboard', 'dashboard', 'Dashboard']],
            'Care' => [
                ['patient.bookings.index', 'calendar', 'My bookings'],
                ['directory', 'search', 'Find a doctor'],
                ['patient.questions.index', 'help-circle', 'My questions'],
            ],
            'Account' => [
                ['tickets.index', 'life-buoy', 'Support'],
                ['account.edit', 'sliders', 'Account & security'],
            ],
        ],
    ][$user->role->value];
    $badges = $dashboardBadges ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body x-data="{ nav: false }">
<div class="dash-shell">
    <aside class="dash-sidebar" :class="{ 'open': nav }" aria-label="Dashboard navigation">
        <x-logo :tagline="false" :href="route('home')" />
        <nav class="flex-grow-1">
            @foreach ($menus as $section => $items)
                @php $items = array_filter($items, fn ($i) => Route::has($i[0])); @endphp
                @if ($items)
                    <div class="nav-section">{{ $section }}</div>
                    @foreach ($items as $item)
                        @php
                            [$route, $icon, $label] = $item;
                            $count = isset($item[3]) ? ($badges[$item[3]] ?? 0) : 0;
                            // Dashboards and single-word routes match exactly; sections match their whole group (e.g. bookings.*).
                            $exact = ! str_contains($route, '.') || str_ends_with($route, '.dashboard');
                            $isActive = $exact ? request()->routeIs($route) : request()->routeIs(str($route)->beforeLast('.').'.*');
                        @endphp
                        <a href="{{ route($route) }}" class="nav-link {{ $isActive && $route !== 'directory' ? 'active' : '' }}">
                            <x-icon :name="$icon" /> {{ $label }}
                            @if ($count)<span class="badge rounded-pill text-bg-warning">{{ $count }}</span>@endif
                        </a>
                    @endforeach
                @endif
            @endforeach
        </nav>
        <div class="border-top pt-3 mt-3 d-flex align-items-center gap-2">
            <x-avatar :name="$user->name" :src="$user->practitionerProfile?->headshotUrl()" size="sm" round />
            <div class="small lh-sm min-w-0 flex-grow-1">
                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                <div class="text-muted text-capitalize">{{ $user->role->value }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-link text-muted p-1" title="Log out" aria-label="Log out"><x-icon name="log-out" /></button>
            </form>
        </div>
    </aside>
    <div class="position-fixed top-0 start-0 w-100 h-100 bg-dark opacity-25 d-lg-none" style="z-index:1040" x-show="nav" x-cloak @click="nav = false"></div>

    <div class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-paper btn-sm d-lg-none" @click="nav = true" aria-label="Open navigation"><x-icon name="menu" /></button>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        @hasSection('crumb')<li class="breadcrumb-item active" aria-current="page">@yield('crumb')</li>@endif
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if (Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}" class="btn btn-paper btn-sm position-relative" aria-label="Notifications">
                        <x-icon name="bell" />
                        @if ($unread = $user->unreadNotifications()->count())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unread }}</span>
                        @endif
                    </a>
                @endif
                <a href="{{ route('home') }}" class="btn btn-paper btn-sm d-none d-sm-inline-flex"><x-icon name="globe" /> View site</a>
            </div>
        </div>
        <main class="dash-content" id="main">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
