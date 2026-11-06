@extends('layouts.app')
@php
    $spec = $fixed['specialty'] ?? $filters['specialty'];
    $cond = $fixed['condition'] ?? null;
    $cityM = $fixed['city'] ?? null;
    if ($cond) {
        $heading = 'Specialists for '.$cond->name;
        $intro = 'Verified practitioners who help with '.mb_strtolower($cond->name).'. Compare experience, languages and fees, then book online.';
    } elseif ($spec && $cityM) {
        $heading = $spec->name.' in '.$cityM->name;
        $intro = 'Book an online consultation with a verified '.mb_strtolower($spec->practitioner_label).' based in '.$cityM->name.'.';
    } elseif ($spec) {
        $heading = 'Online '.$spec->name;
        $intro = $spec->summary;
    } else {
        $heading = 'Find a practitioner';
        $intro = 'Every practitioner here has had their identity, degree and licence checked by our team.';
    }
    $hasFilters = $filters['gender'] || $filters['languages'] || $filters['tags'] || $filters['country'] || $filters['sub'] || $filters['fee_min'] !== null || $filters['fee_max'] !== null || ($filters['q'] ?? '') !== '' || (! $cond && $filters['condition']) || (! isset($fixed['specialty']) && $filters['specialty']);
@endphp
@section('title', $heading)
@section('description', \Illuminate\Support\Str::limit(strip_tags($intro ?? ''), 155))
@if ($hasFilters || $results->currentPage() > 1)
    @section('canonical', url()->current())
@endif

@section('content')
<header class="directory-head">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                @if ($spec && isset($fixed['specialty']))
                    <li class="breadcrumb-item"><a href="{{ route('directory') }}">Find a doctor</a></li>
                    @if ($cityM)
                        <li class="breadcrumb-item"><a href="{{ route('specialties.show', $spec) }}">{{ $spec->name }}</a></li>
                    @endif
                @elseif ($cond)
                    <li class="breadcrumb-item"><a href="{{ route('directory') }}">Find a doctor</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $cityM?->name ?? ($cond?->name ?? ($spec && isset($fixed['specialty']) ? $spec->name : 'Find a doctor')) }}</li>
            </ol>
        </nav>
        <h1 class="mb-2">{{ $heading }}</h1>
        @if ($intro)<p class="text-soft mb-0" style="max-width: 44rem">{{ $intro }}</p>@endif

        @if ($cond && $cond->specialties->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="small text-muted fw-semibold align-self-center">Usually treated by:</span>
                @foreach ($cond->specialties as $s)
                    <a href="{{ route('specialties.show', $s) }}" class="chip">{{ $s->name }}</a>
                @endforeach
            </div>
        @elseif ($spec && $spec->children->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach ($spec->children as $child)
                    <a href="{{ request()->fullUrlWithQuery(['sub' => $filters['sub']?->id === $child->id ? null : $child->slug, 'page' => null]) }}" class="chip {{ $filters['sub']?->id === $child->id ? 'active' : '' }}">{{ $child->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
</header>

<div class="container py-4 py-lg-5">
    <div class="row g-4">
        {{-- Filters --}}
        <div class="col-lg-3">
            <button class="btn btn-paper w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filters" aria-expanded="false" aria-controls="filters">
                <x-icon name="sliders" /> Filters
            </button>
            <form method="GET" action="{{ url()->current() }}" id="filters" class="filter-panel collapse d-lg-block" x-data @change="$el.requestSubmit()">
                <div class="filter-group">
                    <label class="filter-title" for="f-q">Search</label>
                    <input id="f-q" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Name, symptom…" @change.stop>
                </div>

                @unless (isset($fixed['specialty']))
                    <div class="filter-group">
                        <label class="filter-title" for="f-spec">Specialty</label>
                        <select id="f-spec" name="specialty" class="form-select">
                            <option value="">All specialties</option>
                            @foreach ($specialties as $s)
                                <option value="{{ $s->slug }}" @selected($filters['specialty']?->id === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                @if ($filters['sub'])<input type="hidden" name="sub" value="{{ $filters['sub']->slug }}">@endif

                @unless ($cond)
                    <div class="filter-group">
                        <label class="filter-title" for="f-cond">Symptom or condition</label>
                        <select id="f-cond" name="condition" class="form-select">
                            <option value="">Any</option>
                            @foreach ($conditions as $c)
                                <option value="{{ $c->slug }}" @selected($filters['condition']?->slug === $c->slug)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="filter-group">
                    <span class="filter-title">Practitioner gender</span>
                    <div class="segmented w-100">
                        @foreach (['' => 'Any', 'female' => 'Female', 'male' => 'Male'] as $val => $label)
                            <input type="radio" name="gender" id="fg-{{ $val ?: 'any' }}" value="{{ $val }}" @checked(($filters['gender'] ?? '') === $val)>
                            <label for="fg-{{ $val ?: 'any' }}" class="flex-fill text-center">{{ $label }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="filter-group">
                    <span class="filter-title">Preferred language</span>
                    @foreach ($languages as $lang)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="languages[]" value="{{ $lang->code }}" id="fl-{{ $lang->code }}" @checked(in_array($lang->code, $filters['languages'], true))>
                            <label class="form-check-label" for="fl-{{ $lang->code }}">{{ $lang->name }}</label>
                        </div>
                    @endforeach
                </div>

                @unless ($cityM)
                    <div class="filter-group">
                        <label class="filter-title" for="f-country">Practitioner's country</label>
                        <select id="f-country" name="country" class="form-select">
                            <option value="">Anywhere</option>
                            @foreach ($countries as $c)
                                <option value="{{ strtolower($c->iso2) }}" @selected($filters['country']?->id === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @if ($cities->isNotEmpty())
                            <select name="city" class="form-select mt-2" aria-label="City">
                                <option value="">All cities</option>
                                @foreach ($cities as $c)
                                    <option value="{{ $c->slug }}" @selected($filters['city']?->id === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endunless

                <div class="filter-group">
                    <span class="filter-title">Fee per session</span>
                    <select name="currency" class="form-select mb-2" aria-label="Currency">
                        <option value="">Any currency</option>
                        <option value="PKR" @selected($filters['currency'] === 'PKR')>PKR (Pakistan)</option>
                        <option value="USD" @selected($filters['currency'] === 'USD')>USD (International)</option>
                    </select>
                    @if ($filters['currency'])
                        <div class="d-flex gap-2">
                            <input type="number" min="0" name="fee_min" value="{{ $filters['fee_min'] }}" class="form-control" placeholder="Min" aria-label="Minimum fee">
                            <input type="number" min="0" name="fee_max" value="{{ $filters['fee_max'] }}" class="form-control" placeholder="Max" aria-label="Maximum fee">
                        </div>
                    @endif
                </div>

                @if ($tags->isNotEmpty())
                    <div class="filter-group">
                        <span class="filter-title">Certifications & approaches</span>
                        <div class="scroll-list">
                            @foreach ($tags as $tag)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="{{ $tag->slug }}" id="ft-{{ $tag->slug }}" @checked(in_array($tag->slug, $filters['tags'], true))>
                                    <label class="form-check-label" for="ft-{{ $tag->slug }}">{{ $tag->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <div class="filter-group d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Apply</button>
                    <a href="{{ url()->current() }}" class="btn btn-paper">Reset</a>
                </div>
            </form>
        </div>

        {{-- Results --}}
        <div class="col-lg-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="mb-0 text-soft"><strong class="text-ink">{{ $results->total() }}</strong> {{ \Illuminate\Support\Str::plural('practitioner', $results->total()) }} found</p>
                <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                    @foreach (request()->except(['sort', 'page']) as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="sort" class="small text-muted text-nowrap">Sort by</label>
                    <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($sorts as $key => $label)
                            <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($results->isEmpty())
                <x-empty icon="search" title="No one matches all of those filters yet" text="Try removing a filter or two, for example the language or fee range. New practitioners join every week.">
                    <a href="{{ url()->current() }}" class="btn btn-primary mt-2">Clear filters</a>
                </x-empty>
            @else
                <div class="row g-4">
                    @foreach ($results as $profile)
                        <div class="col-md-6 col-xxl-4"><x-practitioner-card :profile="$profile" /></div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $results->onEachSide(1)->links() }}</div>
            @endif

            @if ($spec && isset($fixed['specialty']) && ! $cityM)
                @php $topCities = \App\Models\City::query()->whereHas('country', fn ($q) => $q->where('iso2', 'PK'))->orderBy('id')->limit(8)->get(); @endphp
                <div class="mt-5 pt-4 border-top">
                    <h2 class="h5 mb-3">{{ $spec->name }} by city</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($topCities as $c)
                            <a href="{{ route('specialties.city', [$spec, $c->slug]) }}" class="chip">{{ $spec->practitioner_label }}s in {{ $c->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
