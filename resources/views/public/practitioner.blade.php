@extends('layouts.app')
@php
    $spec = $profile->primarySpecialty;
    $plan = $profile->plan();
    $wa = $profile->publicWhatsapp();
@endphp
@section('title', $profile->displayName().', '.$spec?->practitioner_label.($profile->city ? ' in '.$profile->city->name : ''))
@section('description', \Illuminate\Support\Str::limit($profile->headline ?: strip_tags((string) $profile->bio), 155))
@section('og_type', 'profile')
@if ($profile->headshotUrl()) @section('og_image', $profile->headshotUrl()) @endif
@if ($isPreview) @section('noindex', true) @endif
@section('hide_whatsapp', true)

@section('content')
@if ($isPreview)
    <div class="bg-warning-subtle border-bottom py-2">
        <div class="container small d-flex gap-2 align-items-center"><x-icon name="eye" /> <strong>Preview.</strong> This profile isn't public yet. Only you{{ auth()->user()?->isAdmin() ? ' (admin)' : '' }} can see it.</div>
    </div>
@endif

<section class="profile-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-4">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                @if ($spec)<li class="breadcrumb-item"><a href="{{ route('specialties.show', $spec) }}">{{ $spec->name }}</a></li>@endif
                <li class="breadcrumb-item active" aria-current="page">{{ $profile->displayName() }}</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row gap-4 align-items-md-center">
            <x-avatar :profile="$profile" size="xl" />
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="status tone-success"><x-icon name="badge-check" size="sm" /> Verified</span>
                    @if ($plan?->direct_dealing)<span class="status tone-info">Direct booking</span>@endif
                </div>
                <h1 class="mb-1" style="font-size: clamp(2rem, 4vw, 2.9rem)">{{ $profile->displayName() }}</h1>
                <p class="fs-5 text-forest fw-semibold mb-2">{{ $spec?->practitioner_label }}@if($profile->years_experience) <span class="text-muted fw-normal">· {{ $profile->years_experience }} years' experience</span>@endif</p>
                @if ($profile->headline)<p class="text-soft fs-5 mb-3" style="max-width: 46rem">{{ $profile->headline }}</p>@endif
                <div class="d-flex flex-wrap gap-3 text-soft">
                    <x-rating :value="$profile->rating_avg" :count="$profile->reviews_count" />
                    @if ($profile->locationLabel())<span><x-icon name="map-pin" /> {{ $profile->locationLabel() }}</span>@endif
                    <span><x-icon name="languages" /> {{ $profile->languages->pluck('name')->merge(array_filter([$profile->other_languages]))->implode(', ') }}</span>
                    <span><x-icon name="video" /> Online consultations</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7 col-xl-8">
            @if ($profile->bio)
                <section class="mb-5">
                    <h2 class="h3 mb-3">About {{ $profile->user->firstName() }}</h2>
                    <div class="prose">{!! nl2br(e($profile->bio)) !!}</div>
                </section>
            @endif

            @if ($embed = $profile->videoEmbedUrl())
                <section class="mb-5" x-data="{ play: false }">
                    <h2 class="h4 mb-3">A short introduction</h2>
                    <div class="ratio ratio-16x9 rounded-4 overflow-hidden border bg-forest">
                        <template x-if="play"><iframe :src="'{{ $embed }}?autoplay=1'" title="Introduction video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></template>
                        <button x-show="!play" @click="play = true" class="btn w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white border-0" style="background: #062F2B">
                            <span class="d-grid rounded-circle mb-2" style="width:72px;height:72px;place-items:center;background:#F5F9F8;color:#062F2B"><x-icon name="play" size="lg" /></span>
                            Play introduction
                        </button>
                    </div>
                </section>
            @endif

            <section class="mb-5">
                <h2 class="h4 mb-3">Areas of focus</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($profile->subSpecialties as $s)<span class="chip chip-lg">{{ $s->name }}</span>@endforeach
                    @foreach ($profile->conditions as $c)<a href="{{ route('conditions.show', $c) }}" class="chip chip-lg">{{ $c->name }}</a>@endforeach
                    @foreach ($profile->approvedTags as $t)<span class="chip chip-lg"><x-icon name="tag" size="sm" /> {{ $t->name }}</span>@endforeach
                </div>
            </section>

            <section class="mb-5">
                <h2 class="h4 mb-3">Qualifications & licence</h2>
                <div class="card-soft p-4">
                    <dl class="row mb-0">
                        @if ($profile->qualifications)
                            <dt class="col-sm-4 text-muted fw-semibold">Qualifications</dt><dd class="col-sm-8">{{ $profile->qualifications }}</dd>
                        @endif
                        @if ($profile->licensing_body)
                            <dt class="col-sm-4 text-muted fw-semibold">Registered with</dt><dd class="col-sm-8">{{ $profile->licensing_body }}</dd>
                        @endif
                        <dt class="col-sm-4 text-muted fw-semibold">Verification</dt>
                        <dd class="col-sm-8 mb-0"><x-icon name="shield-check" class="text-forest" /> Identity, degree and licence checked by our team{{ $profile->verified_at ? ' in '.$profile->verified_at->format('F Y') : '' }}.</dd>
                    </dl>
                </div>
            </section>

            <section class="mb-5" id="reviews">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <h2 class="h4 mb-0">Patient reviews</h2>
                    @if ($profile->reviews_count)<x-rating :value="$profile->rating_avg" :count="$profile->reviews_count" />@endif
                </div>
                @forelse ($reviews as $review)
                    <figure class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <x-rating :value="$review->rating" :show-stars="true" />
                            <small class="text-muted">{{ $review->created_at->format('M Y') }}</small>
                        </div>
                        @if ($review->body)<blockquote class="mb-1">{{ $review->body }}</blockquote>@endif
                        <figcaption class="small text-muted">{{ $review->authorLabel() }} · Verified consultation</figcaption>
                    </figure>
                @empty
                    <p class="text-muted">No reviews yet. Reviews can only be left by patients after a completed consultation.</p>
                @endforelse
            </section>

            @if ($answers->isNotEmpty())
                <section class="mb-5">
                    <h2 class="h4 mb-3">Recent answers on Ask a Doctor</h2>
                    @foreach ($answers as $a)
                        <a href="{{ route('questions.show', $a->question) }}" class="qa-item">
                            <h3>{{ $a->question->title }}</h3>
                            <p class="line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($a->body), 160) }}</p>
                        </a>
                    @endforeach
                </section>
            @endif

            @if ($articles->isNotEmpty())
                <section>
                    <h2 class="h4 mb-3">Articles by {{ $profile->displayName() }}</h2>
                    @foreach ($articles as $article)
                        <a href="{{ route('articles.show', $article) }}" class="qa-item">
                            <h3>{{ $article->title }}</h3>
                            <p>{{ $article->excerpt }}</p>
                        </a>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="col-lg-5 col-xl-4" id="book">
            <div class="profile-aside d-grid gap-3">
                @include('public.partials.booking-widget', ['profile' => $profile])

                <div class="card-soft p-4">
                    <h2 class="h6 mb-2">Have a question before booking?</h2>
                    <p class="small text-muted mb-3">
                        @if ($plan?->direct_dealing && $profile->whatsapp_number)
                            Message {{ $profile->user->firstName() }} directly on WhatsApp.
                        @else
                            Our team will answer on WhatsApp, usually within an hour.
                        @endif
                    </p>
                    @if ($wa)
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $wa) }}?text={{ urlencode('Hi, I have a question about booking with '.$profile->displayName().' ('.route('practitioners.show', $profile).')') }}" target="_blank" rel="noopener" class="btn btn-whatsapp w-100"><x-icon name="whatsapp" /> Ask on WhatsApp</a>
                    @endif
                    @if ($profile->showsLinkedin())
                        <a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener nofollow" class="btn btn-paper w-100 mt-2"><x-icon name="linkedin" /> LinkedIn profile</a>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Physician',
    'name' => $profile->displayName(),
    'url' => route('practitioners.show', $profile),
    'image' => $profile->headshotUrl(),
    'description' => $profile->headline,
    'medicalSpecialty' => $spec?->name,
    'knowsLanguage' => $profile->languages->pluck('name')->values(),
    'address' => $profile->city ? ['@type' => 'PostalAddress', 'addressLocality' => $profile->city->name, 'addressCountry' => $profile->country?->iso2] : null,
    'priceRange' => $profile->feeLabel(),
    'aggregateRating' => $profile->reviews_count ? ['@type' => 'AggregateRating', 'ratingValue' => $profile->rating_avg, 'reviewCount' => $profile->reviews_count, 'bestRating' => 5] : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
