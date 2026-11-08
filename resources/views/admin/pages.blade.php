@extends('layouts.dashboard')
@section('title', 'Legal pages')
@section('crumb', 'Legal pages')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="dash-title">Legal pages</h1>
        <p class="text-muted mb-0">Edit the text like a normal document: headings, bold, italic, lists and links. Changes go live as soon as you save.</p>
    </div>
</div>
<div class="alert alert-warning small d-flex gap-2 align-items-start"><i class="ri-error-warning-line fs-5"></i><span>These started as drafts. Please have them reviewed by a lawyer for each country you operate in before launch.</span></div>

@foreach ($pages as $page)
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="card-soft p-4 mb-4">
        @csrf @method('PUT')
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
                <span class="icon-tile sm tone-teal"><i class="ri-file-text-line fs-5"></i></span>
                <div>
                    <h2 class="h5 mb-0">{{ $page->title }}</h2>
                    <div class="small text-muted">Last saved {{ $page->updated_at->diffForHumans() }}</div>
                </div>
            </div>
            <a href="{{ route('pages.show', $page) }}" target="_blank" class="btn btn-paper btn-sm"><i class="ri-external-link-line"></i> View page</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label small" for="title-{{ $page->id }}">Page title</label>
                <input id="title-{{ $page->id }}" name="title" value="{{ old('title', $page->title) }}" class="form-control" required maxlength="120">
            </div>
            <div class="col-md-7">
                <label class="form-label small" for="meta-{{ $page->id }}">Short description <span class="text-muted fw-normal">(shown in Google results)</span></label>
                <input id="meta-{{ $page->id }}" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}" maxlength="170" class="form-control">
            </div>
        </div>

        <label class="form-label small">Page content</label>
        <input type="hidden" name="body" id="body-{{ $page->id }}" value="{{ $page->body }}">
        <div class="editor-shell editor-compact"><div data-rich-editor="body-{{ $page->id }}" data-placeholder="Write the {{ strtolower($page->title) }} here…"></div></div>

        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save {{ $page->title }}</button>
        </div>
    </form>
@endforeach
@endsection
@push('scripts')
@vite('resources/js/editor.js')
@endpush
