@extends('layouts.dashboard')
@section('title', 'Answer questions')
@section('crumb', 'Answer questions')
@section('content')
<h1 class="dash-title">Answer questions</h1>
<p class="text-muted mb-3">Your answers appear publicly with your name and link to your profile, which is a great way for patients to find you. Questions in your specialty are shown first.</p>
@unless ($profile->is_public)
    <div class="alert alert-info">You can answer once your profile is verified and live.</div>
@endunless
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('practitioner.questions.index') }}" class="chip {{ $tab === 'open' ? 'active' : '' }}">Open questions</a>
    <a href="{{ route('practitioner.questions.index', ['tab' => 'mine']) }}" class="chip {{ $tab === 'mine' ? 'active' : '' }}">Answered by me</a>
</div>
@forelse ($questions as $q)
    <div class="card-soft p-4 mb-3" x-data="{ open: false }">
        <div class="small text-muted mb-1">{{ $q->specialty?->name }} · {{ $q->askerLabel() }} · {{ $q->published_at->diffForHumans() }} · {{ $q->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $q->answers_count) }}</div>
        <h2 class="h5 mb-2" style="font-family: inherit">{{ $q->title }}</h2>
        <p class="text-soft mb-3" style="white-space: pre-line">{{ $q->body }}</p>
        <div class="d-flex gap-2">
            <a href="{{ route('questions.show', $q) }}" target="_blank" class="btn btn-sm btn-paper">View public page</a>
            @if ($tab === 'open' && $profile->is_public)<button class="btn btn-sm btn-primary" @click="open = !open">Write an answer</button>@endif
        </div>
        @if ($tab === 'open' && $profile->is_public)
            <form method="POST" action="{{ route('practitioner.answers.store', $q) }}" class="mt-3" x-show="open" x-cloak>
                @csrf
                <textarea name="body" rows="7" class="form-control mb-2" minlength="80" maxlength="5000" required placeholder="Explain in plain language. Suggest when they should seek a consultation. Avoid diagnosing without examination."></textarea>
                <button class="btn btn-primary">Publish answer</button>
            </form>
        @endif
    </div>
@empty
    <x-empty icon="help-circle" title="{{ $tab === 'open' ? 'No open questions right now' : 'You haven\'t answered any questions yet' }}" />
@endforelse
{{ $questions->links() }}
@endsection
