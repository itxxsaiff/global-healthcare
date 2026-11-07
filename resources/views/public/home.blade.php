@extends('layouts.app')
@section('header_overlay', '1')
@section('description', 'Talk to a verified doctor, psychologist, dietitian or therapist online. Search by specialty or symptom, choose a male or female practitioner, and pick your language.')
@php
    $tones = ['teal', 'coral', 'sky', 'amber', 'rose', 'leaf', 'cyan'];
@endphp

@section('content')
{{-- ───────────── Hero ───────────── --}}
<section class="hero-dark">
    <div class="container position-relative" style="z-index:2">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1>Trusted doctors, <br class="d-none d-xl-block"><span class="accent">one video call</span> away.</h1>
                <p class="hero-lead">Online consultations with licence-checked specialists from Pakistan to the UK. Choose by symptom, gender and language, then book a time that suits you.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('directory') }}" class="btn btn-mint btn-lg">Find a doctor <x-icon name="arrow-right" class="icon-arrow" /></a>
                    <a href="{{ route('register.practitioner') }}" class="btn btn-ghost-light btn-lg">Join as practitioner</a>
                </div>
                <div class="hero-stats">
                    <div><x-icon name="users" /><span><strong>{{ $doctorCount }}+</strong><small>Verified practitioners</small></span></div>
                    <div><x-icon name="globe" /><span><strong>{{ $countryCount }}</strong><small>Countries served</small></span></div>
                    <div><x-icon name="shield-check" /><span><strong>100%</strong><small>Licence-checked</small></span></div>
                </div>
            </div>

            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-visual" aria-hidden="true">
                    <div class="globe"></div>
                    <div class="orbit"></div>
                    <div class="halo"></div>
                    <span class="pin p1" style="left: 120px; top: 70px"><x-icon name="map-pin" /></span>
                    <span class="pin p2" style="left: 250px; top: 20px"><x-icon name="map-pin" /></span>
                    <span class="pin p3" style="left: 60px; top: 200px"><x-icon name="map-pin" /></span>

                    <div class="hero-photo"><img src="/images/hero-doctor.webp" alt="" width="720" height="860" fetchpriority="high"></div>

                    <div class="float-card fc-live">
                        <span class="live-tag">Live consultation</span>
                        <img src="/images/call-patient.webp" alt="" width="360" height="250" loading="lazy">
                        <div class="controls">
                            <span><x-icon name="mic" /></span><span><x-icon name="video" /></span><span class="end"><x-icon name="phone-off" /></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <svg class="hero-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 84C380 72 800 30 1100 14 1260 6 1360 8 1440 14V120H0Z" fill="currentColor"/></svg>
</section>

{{-- ───────────── Search ───────────── --}}
<section class="hero-search">
    <div class="container">
        <form action="{{ route('directory') }}" method="GET" class="search-bar" role="search">
            <div class="row g-2 align-items-stretch">
                <div class="col-lg-4 position-relative" x-data="searchSuggest('{{ route('search.suggest') }}')" @click.outside="open = false">
                    <div class="field">
                        <x-icon name="search" />
                        <input type="search" name="q" class="form-control" placeholder="Symptom, specialty or doctor’s name"
                               x-model="q" @input="onInput" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="choose($event)" @focus="open = flat.length > 0"
                               autocomplete="off" aria-label="Search by symptom, specialty or name">
                    </div>
                    <div class="suggest-menu" x-show="open" x-cloak x-transition.opacity>
                        <template x-for="group in groups" :key="group.label">
                            <div>
                                <div class="suggest-group" x-text="group.label"></div>
                                <template x-for="item in group.items" :key="item.url">
                                    <a :href="item.url" :class="{ 'is-active': isActive(item) }"><span x-text="item.label"></span><small x-text="item.hint"></small></a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="field">
                        <x-icon name="stethoscope" />
                        <select name="specialty" class="form-select" aria-label="Specialty">
                            <option value="">All specialties</option>
                            @foreach ($specialties as $s)
                                <option value="{{ $s->slug }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="field">
                        <x-icon name="user" />
                        <select name="gender" class="form-select" aria-label="Practitioner gender">
                            <option value="">Male or female</option>
                            <option value="female">Female practitioner</option>
                            <option value="male">Male practitioner</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="field">
                        <x-icon name="languages" />
                        <select name="languages[]" class="form-select" aria-label="Preferred language">
                            <option value="">Any language</option>
                            @foreach ($languages as $lang)
                                <option value="{{ $lang->code }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-auto">
                    <button class="btn btn-primary btn-search px-4">Search doctors <x-icon name="arrow-right" class="icon-arrow" /></button>
                </div>
            </div>
        </form>
        <div class="popular-row">
            <span>Popular:</span>
            @foreach ($symptoms->take(7) as $symptom)
                <a href="{{ route('conditions.show', $symptom) }}" class="chip">{{ $symptom->name }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- ───────────── Specialties ───────────── --}}
<section class="section pb-4">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Browse by specialty</span>
                <h2 class="section-title">Who would you like to see?</h2>
            </div>
            <a href="{{ route('directory') }}" class="btn-link-arrow">All practitioners <x-icon name="arrow-right" /></a>
        </div>
        <div class="spec-grid">
            @foreach ($specialties->take(7) as $i => $s)
                    <a href="{{ route('specialties.show', $s) }}" class="spec-tile">
                        <span class="icon-tile tone-{{ $tones[$i % count($tones)] }}"><x-icon :name="$s->icon ?? 'stethoscope'" /></span>
                        <strong>{{ $s->name }}</strong>
                        <small>{{ ($n = $specialtyCounts[$s->id] ?? 0) ? $n.' '.\Illuminate\Support\Str::plural('practitioner', $n) : 'Joining soon' }}</small>
                    </a>
            @endforeach
                <a href="{{ route('directory') }}" class="spec-tile spec-all">
                    <span class="icon-tile" style="background:#fff"><x-icon name="list" /></span>
                    <strong>View all {{ $specialties->count() }} <x-icon name="arrow-right" size="sm" /></strong>
                    <small>specialties</small>
                </a>
        </div>
        <p class="small text-muted mt-3 mb-0"><x-icon name="info" size="sm" /> We only list non-emergency outpatient specialties. For emergencies, please go to your nearest hospital.</p>
    </div>
</section>

{{-- ───────────── Plans + How it works ───────────── --}}
<section class="section-tight">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="plans-banner d-flex flex-column">
                    <span class="crown"><x-icon name="crown" /></span>
                    <span class="eyebrow text-mint mb-2">For practitioners</span>
                    <h2 class="mb-2" style="max-width: 15rem">Practitioner subscription plans</h2>
                    <p class="mb-4" style="max-width: 22rem">Get verified once, set your own fees and hours, and reach patients across Pakistan, the Gulf, the UK and North America.</p>
                    <div class="mt-auto">
                        <div class="plan-line"><span>Pakistan · Starter</span><strong>Free + 20% per booking</strong></div>
                        <div class="plan-line"><span>Pakistan · Pro</span><strong>Rs 5,000 / month</strong></div>
                        <div class="plan-line mb-3"><span>International</span><strong>from $30 / month</strong></div>
                        <a href="{{ route('for-practitioners') }}#plans" class="btn btn-cream">View plans <x-icon name="arrow-right" class="icon-arrow" /></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="how-card">
                    <span class="eyebrow">How it works</span>
                    <h2 class="section-title mb-0" style="font-size: clamp(1.4rem, 2.2vw, 1.75rem)">From search to session in four steps</h2>
                    <div class="how-steps">
                        <div class="how-step">
                            <span class="icon-tile tone-teal"><x-icon name="search" /></span>
                            <h3><span class="num">1</span> Find your doctor</h3>
                            <p>Filter by symptom, gender, language and fee.</p>
                        </div>
                        <div class="how-step">
                            <span class="icon-tile tone-sky"><x-icon name="calendar" /></span>
                            <h3><span class="num">2</span> Request a time</h3>
                            <p>Slots are shown in your own time zone.</p>
                        </div>
                        <div class="how-step">
                            <span class="icon-tile tone-amber"><x-icon name="shield-check" /></span>
                            <h3><span class="num">3</span> Confirm & pay</h3>
                            <p>The doctor accepts, you pay securely online.</p>
                        </div>
                        <div class="how-step">
                            <span class="icon-tile tone-coral"><x-icon name="video" /></span>
                            <h3><span class="num">4</span> Talk from home</h3>
                            <p>Join your video or audio session anywhere.</p>
                        </div>
                    </div>
                    <div class="how-trust">
                        <div><x-icon name="shield-check" /> ID, degree & licence verified</div>
                        <div><x-icon name="users" /> Choose a male or female practitioner</div>
                        <div><x-icon name="clock" /> Times shown in your time zone</div>
                        <div><x-icon name="lock" /> Private & confidential</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ───────────── Featured practitioners ───────────── --}}
@if ($featured->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Featured practitioners</span>
                <h2 class="section-title">Verified, well-reviewed and taking patients</h2>
            </div>
            <a href="{{ route('directory') }}" class="btn btn-outline-forest">See all {{ $doctorCount }} <x-icon name="arrow-right" class="icon-arrow" /></a>
        </div>
        <div class="row g-4">
            @foreach ($featured as $profile)
                <div class="col-md-6 col-xl-4"><x-practitioner-card :profile="$profile" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ───────────── Symptom search band ───────────── --}}
<section class="pb-5">
    <div class="container">
        <div class="symptom-band">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <span class="eyebrow">Not sure who to see?</span>
                    <h2 class="section-title mt-2">Start with what you're feeling.</h2>
                    <p class="text-soft mb-0">Pick a symptom and we'll show you the specialists who treat it. No medical vocabulary needed.</p>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($symptoms as $symptom)
                            <a href="{{ route('conditions.show', $symptom) }}" class="chip chip-lg">{{ $symptom->name }} <x-icon name="arrow-right" size="sm" class="text-forest" /></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ───────────── Ask a doctor ───────────── --}}
@if ($questions->isNotEmpty())
<section class="section pt-4">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <span class="eyebrow">Ask a doctor</span>
                <h2 class="section-title mt-2">Free answers from real specialists</h2>
                <p class="text-soft">Post a health question anonymously and verified practitioners answer publicly, so the next person with the same worry finds it too.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('questions.index') }}" class="btn btn-primary">Browse questions</a>
                    @if (Route::has('patient.questions.create'))<a href="{{ route('patient.questions.create') }}" class="btn btn-outline-forest">Ask yours</a>@endif
                </div>
            </div>
            <div class="col-lg-8">
                @foreach ($questions as $i => $q)
                    <a href="{{ route('questions.show', $q) }}" class="qa-item">
                        <span class="icon-tile sm tone-{{ $tones[$i % count($tones)] }}"><x-icon name="message-circle" /></span>
                        <div class="min-w-0">
                            <div class="small text-muted mb-1">{{ $q->specialty?->name }} · {{ $q->published_at->diffForHumans() }}</div>
                            <h3>{{ $q->title }}</h3>
                            <p class="line-clamp-2">{{ \Illuminate\Support\Str::limit($q->body, 180) }}</p>
                            <div class="small mt-2 fw-semibold {{ $q->answers_count ? 'text-forest' : 'text-muted' }}">
                                {{ $q->answers_count ? $q->answers_count.' doctor '.\Illuminate\Support\Str::plural('answer', $q->answers_count) : 'Awaiting an answer' }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ───────────── Closing call to action ───────────── --}}
<section class="pb-5 mb-lg-4">
    <div class="container">
        <div class="cta-band">
            <span class="cta-rings"></span>
            <div class="row g-4 align-items-center position-relative">
                <div class="col-lg-7">
                    <span class="eyebrow text-mint">Ready when you are</span>
                    <h2 class="section-title mt-2">Feeling unwell, or just need advice? Talk to someone today.</h2>
                    <p class="mb-0" style="max-width: 34rem">Pick a verified specialist, choose a time in your own time zone, and speak from home in the language you're most comfortable with.</p>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                        <a href="{{ route('directory') }}" class="btn btn-mint btn-lg">Find a doctor <x-icon name="arrow-right" class="icon-arrow" /></a>
                        <a href="{{ route('questions.index') }}" class="btn btn-ghost-light btn-lg">Ask a free question</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'MedicalBusiness',
    'name' => config('app.name'),
    'url' => url('/'),
    'description' => 'Online directory and booking platform for verified non-emergency healthcare practitioners.',
    'medicalSpecialty' => $specialties->pluck('name')->values(),
    'areaServed' => ['Pakistan', 'United Arab Emirates', 'Saudi Arabia', 'United Kingdom', 'United States', 'Canada', 'Qatar', 'Oman', 'Kuwait'],
    'potentialAction' => ['@type' => 'SearchAction', 'target' => route('directory').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
