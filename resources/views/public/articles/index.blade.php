@extends('layouts.app')
@section('title', $specialty ? $specialty->name.' articles' : 'Health articles written by verified practitioners')
@section('description', 'Practical, plain-language health articles and guides written by verified doctors, psychologists and nutritionists.')
@section('content')
<header class="directory-head">
    <div class="container">
        <span class="eyebrow mb-2">Health articles</span>
        <h1 class="mb-2">{{ $specialty ? $specialty->name : 'Written by practitioners, for real people' }}</h1>
        <p class="text-soft mb-0" style="max-width: 42rem">Every article is written by a verified practitioner and reviewed by our team before it's published.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('articles.index') }}" class="chip {{ ! $specialty ? 'active' : '' }}">All</a>
            @foreach ($specialties as $s)
                <a href="{{ route('articles.index', ['topic' => $s->slug]) }}" class="chip {{ $specialty?->id === $s->id ? 'active' : '' }}">{{ $s->name }}</a>
            @endforeach
        </div>
    </div>
</header>
<div class="container py-5">
    @if ($articles->isEmpty())
        <x-empty icon="book-open" title="Articles are on their way" text="Our practitioners are writing the first guides now. In the meantime, browse questions they've already answered.">
            <a href="{{ route('questions.index') }}" class="btn btn-primary mt-2">Browse Ask a Doctor</a>
        </x-empty>
    @else
        <div class="row g-4">
            @foreach ($articles as $article)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('articles.show', $article) }}" class="card-soft card-hover d-block h-100 text-reset overflow-hidden">
                        @if ($article->imageUrl())
                            <img src="{{ $article->imageUrl() }}" alt="" class="w-100" style="aspect-ratio: 1200/630; object-fit: cover" loading="lazy" width="1200" height="630">
                        @else
                            <div class="bg-sage d-grid" style="aspect-ratio: 1200/630; place-items:center"><x-icon name="book-open" size="lg" class="text-forest" /></div>
                        @endif
                        <div class="p-4">
                            <div class="small text-muted mb-2">{{ $article->specialty?->name }} · {{ $article->readingMinutes() }} min read</div>
                            <h2 class="h5 font-serif mb-2" style="font-family: var(--bs-font-serif, Fraunces)">{{ $article->title }}</h2>
                            <p class="small text-soft mb-3 line-clamp-3">{{ $article->excerpt }}</p>
                            <div class="small fw-semibold">{{ $article->practitioner?->displayName() ?? $article->author->name }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $articles->links() }}</div>
    @endif
</div>
@endsection
