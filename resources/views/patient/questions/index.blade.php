@extends('layouts.dashboard')
@section('title', 'My questions')
@section('crumb', 'My questions')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">My questions</h1>
        <p class="text-muted mb-0">Only you can see this list. Published questions show as "Anonymous".</p>
    </div>
    <a href="{{ route('patient.questions.create') }}" class="btn btn-clay"><x-icon name="plus" /> Ask a question</a>
</div>
@forelse ($questions as $q)
    <div class="card-soft p-3 mb-2 d-flex flex-wrap gap-3 align-items-center">
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $q->title }}</div>
            <div class="small text-muted">{{ $q->specialty?->name }} · asked {{ $q->created_at->diffForHumans() }} · {{ $q->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $q->answers_count) }}</div>
        </div>
        <x-status :status="$q->status" />
        @if ($q->status === \App\Enums\ModerationStatus::Approved)<a href="{{ route('questions.show', $q) }}" class="btn btn-sm btn-paper">View</a>@endif
    </div>
@empty
    <x-empty icon="help-circle" title="You haven't asked anything yet" text="Ask a general health question and verified specialists will answer publicly, without your name.">
        <a href="{{ route('patient.questions.create') }}" class="btn btn-primary">Ask your first question</a>
    </x-empty>
@endforelse
{{ $questions->links() }}
@endsection
