@extends('layouts.dashboard')
@section('title', $article->exists ? 'Edit article' : 'New article')
@section('crumb', $article->exists ? 'Edit article' : 'New article')
@php
    $locked = $article->status === \App\Enums\ArticleStatus::Pending;
    $ranges = collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->wordRange()]);
@endphp
@section('content')
<a href="{{ route('practitioner.articles.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> My articles</a>
@if ($article->exists)
    <div class="d-flex align-items-center gap-2 mb-3"><x-status :status="$article->status" />
        @if ($article->status === \App\Enums\ArticleStatus::Approved)<a href="{{ route('articles.show', $article) }}" target="_blank" class="small">View live <x-icon name="external" size="sm" /></a>@endif
    </div>
@endif
@if ($article->status === \App\Enums\ArticleStatus::Rejected && $article->rejection_reason)
    <div class="alert alert-warning"><strong>Note from our team:</strong> {{ $article->rejection_reason }}</div>
@elseif ($locked)
    <div class="alert alert-info">This article is being reviewed, so editing is paused.</div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ $article->exists ? route('practitioner.articles.update', $article) : route('practitioner.articles.store') }}">
    @csrf @if ($article->exists) @method('PUT') @endif
    <fieldset @disabled($locked)>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card-soft p-4">
                <div class="segmented mb-3">
                    @foreach ($types as $t)
                        <input type="radio" name="type" id="type-{{ $t->value }}" value="{{ $t->value }}" @checked(old('type', $article->type?->value ?? 'standard') === $t->value)>
                        <label for="type-{{ $t->value }}">{{ $t->label() }} · {{ number_format($t->wordRange()[0]) }}–{{ number_format($t->wordRange()[1]) }} words</label>
                    @endforeach
                </div>
                <input name="title" value="{{ old('title', $article->title) }}" maxlength="180" class="form-control form-control-lg font-serif border-0 px-0 fs-2 @error('title') is-invalid @enderror" placeholder="Article title" style="font-family: 'Fraunces Variable', Georgia, serif; box-shadow:none">
                <x-field-error name="title" />
                <div class="d-flex justify-content-end mb-2"><span id="word-counter" class="word-counter"></span></div>
                <div class="editor-shell"><div id="editor" data-ranges="{{ $ranges->toJson() }}"></div></div>
                <input type="hidden" name="body" id="body" value="{{ old('body', $article->body) }}">
                <x-field-error name="body" />
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-soft p-4 mb-4">
                <span class="form-label d-block">Featured image</span>
                @if ($article->imageUrl())<img src="{{ $article->imageUrl() }}" alt="" class="w-100 rounded-3 mb-2" style="aspect-ratio:1200/630;object-fit:cover">@endif
                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" class="form-control @error('featured_image') is-invalid @enderror"
                       data-max-kb="{{ \App\Models\Setting::get('article_image_max_upload_kb', 2048) }}" data-max-message="Image is too large. Please upload one under 2MB.">
                <div class="invalid-feedback js-file-error"></div>
                <x-field-error name="featured_image" />
                <p class="form-hint mb-0">Recommended 1200×630 px. We crop it and compress it to a WebP under 200 KB automatically, so the site stays fast.</p>
            </div>
            <div class="card-soft p-4 mb-4">
                <div class="mb-3">
                    <label class="form-label" for="specialty_id">Topic</label>
                    <select id="specialty_id" name="specialty_id" class="form-select">
                        <option value="">General health</option>
                        @foreach ($specialties as $s)<option value="{{ $s->id }}" @selected(old('specialty_id', $article->specialty_id) == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="excerpt">Summary <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="300" class="form-control">{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>
                <details>
                    <summary class="small fw-semibold">SEO settings</summary>
                    <div class="mt-2">
                        <input name="meta_title" maxlength="70" value="{{ old('meta_title', $article->meta_title) }}" class="form-control form-control-sm mb-2" placeholder="Search title (max 70)">
                        <textarea name="meta_description" maxlength="170" rows="2" class="form-control form-control-sm" placeholder="Search description (max 170)">{{ old('meta_description', $article->meta_description) }}</textarea>
                    </div>
                </details>
            </div>
            <div class="d-grid gap-2">
                <button name="action" value="submit" class="btn btn-primary btn-lg">Submit for review</button>
                <button name="action" value="draft" class="btn btn-paper">Save draft</button>
            </div>
        </div>
    </div>
    </fieldset>
</form>
@endsection
@push('scripts')
@vite('resources/js/editor.js')
@endpush
