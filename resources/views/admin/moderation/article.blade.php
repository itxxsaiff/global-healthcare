@extends('layouts.dashboard')
@section('title', $article->title)
@section('crumb', 'Article review')
@php [$min, $max] = $article->type->wordRange(); @endphp
@section('content')
<a href="{{ route('admin.articles.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Articles</a>
<div class="row g-4">
    <div class="col-xl-8">
        <article class="card-soft p-4 p-lg-5">
            @if ($article->imageUrl())<img src="{{ $article->imageUrl() }}" alt="" class="w-100 rounded-3 mb-4" style="aspect-ratio:1200/630;object-fit:cover">@endif
            <h1 class="mb-2">{{ $article->title }}</h1>
            <p class="lead text-soft">{{ $article->excerpt }}</p>
            <div class="prose">{!! $article->body !!}</div>
        </article>
    </div>
    <div class="col-xl-4">
        <div class="card-soft p-4 mb-3 small">
            <div class="mb-1"><span class="text-muted">Author:</span> {{ $article->author->name }}</div>
            <div class="mb-1"><span class="text-muted">Type:</span> {{ $article->type->label() }}</div>
            <div class="mb-1"><span class="text-muted">Words:</span> <span class="{{ $article->word_count < $min || $article->word_count > $max ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ number_format($article->word_count) }}</span> (allowed {{ number_format($min) }}–{{ number_format($max) }})</div>
            <div class="mb-1"><span class="text-muted">Image:</span> {{ $article->featured_image_path ? number_format(Storage::disk('public')->size($article->featured_image_path) / 1024).' KB WebP' : 'none' }}</div>
            <div><span class="text-muted">Status:</span> <x-status :status="$article->status" /></div>
        </div>
        <div class="card-soft p-4">
            <form method="POST" action="{{ route('admin.articles.update', $article) }}" class="mb-3">
                @csrf @method('PATCH')
                <input type="hidden" name="decision" value="approve">
                <button class="btn btn-primary w-100"><x-icon name="check" /> Approve & publish</button>
            </form>
            <form method="POST" action="{{ route('admin.articles.update', $article) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="decision" value="reject">
                <textarea name="reason" rows="3" class="form-control mb-2 @error('reason') is-invalid @enderror" placeholder="What should the author change?"></textarea>
                <x-field-error name="reason" />
                <button class="btn btn-paper w-100">Send back</button>
            </form>
        </div>
    </div>
</div>
@endsection
