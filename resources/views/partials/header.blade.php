<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header {{ View::hasSection('header_overlay') ? 'header-overlay' : '' }}">
    <nav class="navbar navbar-expand-lg" aria-label="Main">
        <div class="container">
            <x-logo />

            <div class="collapse navbar-collapse justify-content-center d-none d-lg-flex">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('directory*', 'specialties.*', 'conditions.*') ? 'active' : '' }}" href="{{ route('directory') }}">Find a doctor</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Specialties</a>
                        <div class="dropdown-menu p-2" style="min-width: 540px">
                            <div class="row g-0">
                                @foreach (\App\Models\Specialty::tree()->chunk(7) as $chunk)
                                    <div class="col-6">
                                        @foreach ($chunk as $s)
                                            <a class="dropdown-item rounded-2 py-2" href="{{ route('specialties.show', $s) }}">{{ $s->name }}</a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('questions.*') ? 'active' : '' }}" href="{{ route('questions.index') }}">Ask a doctor</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Health articles</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('for-practitioners') ? 'active' : '' }}" href="{{ route('for-practitioners') }}">For practitioners</a></li>
                </ul>
            </div>

            <div class="d-flex align-items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-join d-none d-sm-inline-flex"><x-icon name="dashboard" /> My dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-forest btn-login d-none d-sm-inline-flex">Log in</a>
                    <a href="{{ route('register.practitioner') }}" class="btn btn-primary btn-join d-none d-md-inline-flex">Join as practitioner</a>
                @endauth
                <button class="btn btn-icon btn-menu d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu">
                    <x-icon name="menu" />
                </button>
            </div>
        </div>
    </nav>
</header>

<div class="offcanvas offcanvas-end offcanvas-nav" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header">
        <x-logo id="mobileNavLabel" />
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <nav class="nav flex-column">
            <a class="nav-link" href="{{ route('directory') }}"><x-icon name="search" /> Find a doctor</a>
            <a class="nav-link" href="{{ route('questions.index') }}"><x-icon name="message-circle" /> Ask a doctor</a>
            <a class="nav-link" href="{{ route('articles.index') }}"><x-icon name="book-open" /> Health articles</a>
            <a class="nav-link" href="{{ route('for-practitioners') }}"><x-icon name="stethoscope" /> For practitioners</a>
        </nav>
        <div class="mt-auto d-grid gap-2 pt-4">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">My dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create a free account</a>
                <a href="{{ route('register.practitioner') }}" class="btn btn-outline-forest btn-lg">Join as practitioner</a>
                <a href="{{ route('login') }}" class="btn btn-paper btn-lg">Log in</a>
            @endauth
        </div>
    </div>
</div>
