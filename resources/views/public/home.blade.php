@extends('layouts.app')
@section('description', 'Talk to a verified doctor, psychologist, dietitian or therapist online. Search by specialty or symptom, choose a male or female practitioner, and pick your language.')

@section('content')
{{-- ───────────── Hero ───────────── --}}
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="eyebrow mb-3"><x-icon name="badge-check" size="sm" /> Every practitioner licence-checked</span>
                <h1 class="mb-4">Talk to a doctor who actually <x-scribble>listens</x-scribble>.</h1>
                <p class="hero-lead mb-4">Online consultations with verified specialists from Pakistan to the UK. Search by what you're feeling, choose who you're comfortable with, and speak in your own language.</p>

                <form action="{{ route('directory') }}" method="GET" class="search-card" role="search">
                    <div class="row g-2 align-items-stretch">
                        <div class="col-12 position-relative" x-data="searchSuggest('{{ route('search.suggest') }}')" @click.outside="open = false">
                            <div class="search-input">
                                <x-icon name="search" />
                                <input type="search" name="q" class="form-control form-control-lg border-0 bg-paper" placeholder="Try “acne”, “anxiety” or “gynecologist”"
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
                        <div class="col-sm-5">
                            <div class="segmented w-100 h-100" role="radiogroup" aria-label="Practitioner gender">
                                <input type="radio" name="gender" id="hg-any" value="" checked><label for="hg-any" class="flex-fill text-center">Any</label>
                                <input type="radio" name="gender" id="hg-female" value="female"><label for="hg-female" class="flex-fill text-center">Female</label>
                                <input type="radio" name="gender" id="hg-male" value="male"><label for="hg-male" class="flex-fill text-center">Male</label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <select name="languages[]" class="form-select h-100" aria-label="Preferred language">
                                <option value="">Any language</option>
                                @foreach ($languages as $lang)
                                    <option value="{{ $lang->code }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3 d-grid">
                            <button class="btn btn-primary btn-lg">Search</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                    <span class="text-muted small fw-semibold me-1">People often search:</span>
                    @foreach ($symptoms->take(6) as $symptom)
                        <a href="{{ route('conditions.show', $symptom) }}" class="chip">{{ $symptom->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-5">
                <div class="hero-collage" aria-hidden="true">
                    <div class="note">Female doctor, speaks Urdu, evening slots. ✓</div>
                    @foreach ($featured->take(3) as $i => $p)
                        <div class="collage-card c{{ $i + 1 }}">
                            <div class="d-flex gap-3 align-items-center">
                                <x-avatar :profile="$p" size="md" />
                                <div class="min-w-0">
                                    <div class="name">{{ $p->displayName() }}</div>
                                    <div class="small text-forest fw-semibold">{{ $p->primarySpecialty?->practitioner_label }}</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 small">
                                <x-rating :value="$p->rating_avg" :count="$p->reviews_count" :show-stars="false" />
                                <span class="text-muted"><x-icon name="languages" size="sm" /> {{ $p->languages->pluck('name')->take(2)->implode(' · ') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ───────────── Trust strip ───────────── --}}
<section class="trust-strip py-3">
    <div class="container">
        <div class="row g-3 justify-content-between">
            <div class="col-6 col-lg-auto item"><x-icon name="shield-check" /> ID, degree & licence verified</div>
            <div class="col-6 col-lg-auto item"><x-icon name="users" /> Choose male or female practitioners</div>
            <div class="col-6 col-lg-auto item"><x-icon name="clock" /> Times shown in your time zone</div>
            <div class="col-6 col-lg-auto item"><x-icon name="lock" /> Private & confidential</div>
        </div>
    </div>
</section>

{{-- ───────────── Specialties ───────────── --}}
<section class="section">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <span class="eyebrow mb-2">Browse by specialty</span>
                <h2 class="section-title mb-0">Who would you like to see?</h2>
            </div>
            <a href="{{ route('directory') }}" class="btn-link-arrow">All practitioners <x-icon name="arrow-right" /></a>
        </div>
        <div class="row g-3">
            @foreach ($specialties as $s)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <a href="{{ route('specialties.show', $s) }}" class="specialty-tile">
                        <span class="tile-icon"><x-icon :name="$s->icon ?? 'stethoscope'" /></span>
                        <span>
                            <strong>{{ $s->name }}</strong>
                            <small>{{ ($n = $specialtyCounts[$s->id] ?? 0) ? $n.' '.\Illuminate\Support\Str::plural('practitioner', $n) : 'Joining soon' }}</small>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-3 mb-0"><x-icon name="info" size="sm" /> We only list non-emergency outpatient specialties. For emergencies, please go to your nearest hospital.</p>
    </div>
</section>

{{-- ───────────── Symptom search band ───────────── --}}
<section class="pb-5">
    <div class="container">
        <div class="symptom-band">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <span class="eyebrow mb-2">Not sure who to see?</span>
                    <h2 class="section-title">Start with what you're feeling.</h2>
                    <p class="text-soft mb-0">Pick a symptom and we'll show you the specialists who treat it. No medical vocabulary needed.</p>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($symptoms as $symptom)
                            <a href="{{ route('conditions.show', $symptom) }}" class="chip chip-lg">{{ $symptom->name }} <x-icon name="arrow-right" size="sm" class="text-muted" /></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ───────────── How it works ───────────── --}}
<section class="section bg-paper-deep border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <span class="eyebrow mb-2">How it works</span>
            <h2 class="section-title">From search to session in three steps</h2>
        </div>
        <div class="row g-4 g-lg-5">
            <div class="col-md-4 step">
                <div class="step-num">01</div>
                <h3>Find your person</h3>
                <p>Filter by specialty, symptom, gender, language and fee. Read real reviews from patients who've had a consultation.</p>
            </div>
            <div class="col-md-4 step">
                <div class="step-num">02</div>
                <h3>Pick a time that suits you</h3>
                <p>Slots appear in your local time, wherever you are. Send a request and the practitioner confirms it, usually within a few hours.</p>
            </div>
            <div class="col-md-4 step">
                <div class="step-num">03</div>
                <h3>Talk from home</h3>
                <p>Where payment is needed, pay securely online. Then join your session from wherever you are. Follow-ups are just as easy.</p>
            </div>
        </div>
    </div>
</section>

{{-- ───────────── Featured practitioners ───────────── --}}
@if ($featured->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <span class="eyebrow mb-2">Featured practitioners</span>
                <h2 class="section-title mb-0">Verified, well-reviewed, and taking patients</h2>
            </div>
            <a href="{{ route('directory') }}" class="btn-link-arrow">See all {{ $doctorCount }} <x-icon name="arrow-right" /></a>
        </div>
        <div class="row g-4">
            @foreach ($featured as $profile)
                <div class="col-md-6 col-xl-4"><x-practitioner-card :profile="$profile" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ───────────── Ask a doctor ───────────── --}}
@if ($questions->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <span class="eyebrow mb-2">Ask a doctor</span>
                <h2 class="section-title">Free answers from real specialists</h2>
                <p class="text-soft">Post a health question anonymously and verified practitioners answer publicly, so the next person with the same worry finds it too.</p>
                <a href="{{ route('questions.index') }}" class="btn btn-paper mt-2">Browse questions</a>
            </div>
            <div class="col-lg-8">
                @foreach ($questions as $q)
                    <a href="{{ route('questions.show', $q) }}" class="qa-item">
                        <div class="small text-muted mb-1">{{ $q->specialty?->name }} · {{ $q->published_at->diffForHumans() }}</div>
                        <h3>{{ $q->title }}</h3>
                        <p class="line-clamp-2">{{ \Illuminate\Support\Str::limit($q->body, 180) }}</p>
                        <div class="small mt-2 fw-semibold {{ $q->answers_count ? 'text-forest' : 'text-muted' }}">
                            <x-icon name="message-circle" size="sm" /> {{ $q->answers_count ? $q->answers_count.' doctor '.\Illuminate\Support\Str::plural('answer', $q->answers_count) : 'Awaiting an answer' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ───────────── For practitioners ───────────── --}}
<section class="pb-5 mb-lg-4">
    <div class="container">
        <div class="join-band">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <span class="eyebrow mb-2" style="color:#E9B79E">For practitioners</span>
                    <h2 class="section-title">Grow your online practice, on your terms.</h2>
                    <p class="mb-4">Get verified once, set your own fees and hours, and reach patients across Pakistan, the Gulf, the UK and North America.</p>
                    <a href="{{ route('register.practitioner') }}" class="btn btn-cream btn-lg">Apply to join</a>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="plan-mini">
                                <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.08em">Pakistan · Starter</div>
                                <strong>Free</strong>
                                <p class="small mb-0 mt-1">List for free. We take 20% only on consultations booked and paid through us.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="plan-mini">
                                <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.08em">Pakistan · Pro</div>
                                <strong>Rs 5,000<span class="fs-6 fw-normal">/mo</span></strong>
                                <p class="small mb-0 mt-1">0% commission, deal directly with patients, LinkedIn on your profile.</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="plan-mini d-sm-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="small text-uppercase fw-bold mb-1" style="letter-spacing:.08em">International</div>
                                    <strong>$30<span class="fs-6 fw-normal">/mo</span></strong> <span class="small">· $150 for 6 months · $280 a year</span>
                                </div>
                                <p class="small mb-0 mt-2 mt-sm-0" style="max-width:16rem">0% commission and direct client dealing, billed automatically.</p>
                            </div>
                        </div>
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
