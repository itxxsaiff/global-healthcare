@extends('layouts.dashboard')
@section('title', 'My articles')
@section('crumb', 'My articles')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">My articles</h1>
        <p class="text-muted mb-0">Write practical health articles. Each one links to your profile once published.</p>
    </div>
    <a href="{{ route('practitioner.articles.create') }}" class="btn btn-primary"><x-icon name="plus" /> New article</a>
</div>
@forelse ($articles as $a)
    <a href="{{ route('practitioner.articles.edit', $a) }}" class="card-soft card-hover p-3 mb-2 d-flex flex-wrap gap-3 align-items-center text-reset text-decoration-none">
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $a->title }}</div>
            <div class="small text-muted">{{ $a->type->label() }} · {{ number_format($a->word_count) }} words · updated {{ $a->updated_at->diffForHumans() }}</div>
        </div>
        <x-status :status="$a->status" />
    </a>
@empty
    <x-empty icon="book-open" title="No articles yet" text="Standard articles are 600–1,000 words; detailed guides 1,200–1,500. Our team reviews each one before it goes live.">
        <a href="{{ route('practitioner.articles.create') }}" class="btn btn-primary">Write your first article</a>
    </x-empty>
@endforelse
{{ $articles->links() }}
@endsection
