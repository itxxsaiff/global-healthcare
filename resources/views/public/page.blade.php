@extends('layouts.app')
@section('title', $page->title)
@section('description', $page->meta_description)
@section('content')
<header class="directory-head">
    <div class="container container-narrow">
        <span class="eyebrow mb-2">Legal</span>
        <h1 class="mb-1">{{ $page->title }}</h1>
        <p class="text-muted small mb-0">Last updated {{ $page->updated_at->format('j F Y') }}</p>
    </div>
</header>
<article class="container container-narrow py-5 prose">
    {!! $page->body !!}
</article>
@endsection
