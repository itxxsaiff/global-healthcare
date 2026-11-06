@extends('layouts.dashboard')
@section('title', 'Legal pages')
@section('crumb', 'Legal pages')
@section('content')
<h1 class="dash-title">Legal pages</h1>
<div class="alert alert-warning small">These started as drafts. Please have them reviewed by a lawyer for each country you operate in before launch.</div>
@foreach ($pages as $page)
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="card-soft p-4 mb-4">
        @csrf @method('PUT')
        <div class="d-flex justify-content-between mb-2"><input name="title" value="{{ $page->title }}" class="form-control fw-semibold" style="max-width: 24rem"><a href="{{ route('pages.show', $page) }}" target="_blank" class="small">View page</a></div>
        <input name="meta_description" value="{{ $page->meta_description }}" maxlength="170" class="form-control form-control-sm mb-2" placeholder="Search description">
        <textarea name="body" rows="12" class="form-control font-monospace small">{{ $page->body }}</textarea>
        <div class="form-hint">HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;, &lt;a&gt;.</div>
        <button class="btn btn-primary btn-sm mt-2">Save {{ $page->title }}</button>
    </form>
@endforeach
@endsection
