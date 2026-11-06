@extends('layouts.app')
@section('title', $article->meta_title ?: $article->title)
@section('description', $article->meta_description ?: $article->excerpt)
@section('og_type', 'article')
@if ($article->imageUrl()) @section('og_image', $article->imageUrl()) @endif
@section('content')
@php $doc = $article->practitioner; @endphp
<article class="py-5">
    <header class="container container-narrow mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">Health articles</a></li>
                @if ($article->specialty)<li class="breadcrumb-item"><a href="{{ route('articles.index', ['topic' => $article->specialty->slug]) }}">{{ $article->specialty->name }}</a></li>@endif
            </ol>
        </nav>
        <h1 class="mb-3" style="font-size: clamp(2rem, 4.2vw, 3rem)">{{ $article->title }}</h1>
        @if ($article->excerpt)<p class="lead text-soft">{{ $article->excerpt }}</p>@endif
        <div class="d-flex align-items-center gap-3 mt-4">
            @if ($doc)<x-avatar :profile="$doc" size="sm" round />@endif
            <div class="small">
                <div class="fw-semibold">
                    @if ($doc)<a href="{{ route('practitioners.show', $doc) }}" class="text-ink">{{ $doc->displayName() }}</a>@else{{ $article->author->name }}@endif
                </div>
                <div class="text-muted">{{ $article->published_at->format('j F Y') }} · {{ $article->readingMinutes() }} min read</div>
            </div>
        </div>
    </header>
    @if ($article->imageUrl())
        <div class="container mb-5" style="max-width: 1000px">
            <img src="{{ $article->imageUrl() }}" alt="" class="w-100 rounded-4" width="1200" height="630" style="aspect-ratio: 1200/630; object-fit: cover">
        </div>
    @endif
    <div class="container container-narrow">
        <div class="prose">{!! $article->body !!}</div>

        @if ($doc)
            <div class="card-soft p-4 mt-5 d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
                <x-avatar :profile="$doc" size="md" />
                <div class="flex-grow-1">
                    <div class="small text-muted">Written by</div>
                    <div class="fw-bold">{{ $doc->displayName() }}</div>
                    <div class="small text-forest fw-semibold">{{ $doc->primarySpecialty?->practitioner_label }}</div>
                </div>
                @if ($doc->is_public)<a href="{{ route('practitioners.show', $doc) }}" class="btn btn-primary">View profile & book</a>@endif
            </div>
        @endif
        <p class="small text-muted mt-4">This article is for general information and isn't a substitute for a consultation.</p>
    </div>
</article>
@if ($more->isNotEmpty())
<section class="container pb-5">
    <h2 class="h5 mb-3">Keep reading</h2>
    <div class="row g-3">
        @foreach ($more as $m)
            <div class="col-md-4"><a href="{{ route('articles.show', $m) }}" class="card-soft card-hover d-block p-4 h-100 text-reset"><div class="small text-muted mb-1">{{ $m->readingMinutes() }} min read</div><div class="fw-bold">{{ $m->title }}</div></a></div>
        @endforeach
    </div>
</section>
@endif
@endsection

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $article->excerpt,
    'image' => $article->imageUrl(),
    'datePublished' => $article->published_at->toIso8601String(),
    'dateModified' => $article->updated_at->toIso8601String(),
    'wordCount' => $article->word_count,
    'author' => $doc ? ['@type' => 'Person', 'name' => $doc->displayName(), 'url' => route('practitioners.show', $doc)] : ['@type' => 'Organization', 'name' => config('app.name')],
    'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
    'mainEntityOfPage' => route('articles.show', $article),
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
