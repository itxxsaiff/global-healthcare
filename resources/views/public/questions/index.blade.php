@extends('layouts.app')
@section('title', $specialty ? $specialty->name.' questions answered by doctors' : 'Ask a Doctor: free answers from verified specialists')
@section('description', 'Browse anonymous health questions answered publicly by verified doctors, psychologists and therapists.'.($specialty ? ' Topic: '.$specialty->name.'.' : ''))
@section('content')
<header class="directory-head">
    <div class="container">
        <div class="row g-4 align-items-end">
            <div class="col-lg-7">
                <span class="eyebrow mb-2">Ask a doctor</span>
                <h1 class="mb-2">{{ $specialty ? $specialty->name.': questions & answers' : 'Real questions. Answers from verified specialists.' }}</h1>
                <p class="text-soft mb-0">Ask anonymously. Your name is never shown. Answers are general guidance, not a diagnosis.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <a href="{{ Route::has('patient.questions.create') ? route('patient.questions.create') : route('register.patient') }}" class="btn btn-clay btn-lg"><x-icon name="plus" /> Ask a question</a>
            </div>
        </div>
    </div>
</header>

<div class="container py-5">
    <div class="row g-5">
        <aside class="col-lg-3">
            <form method="GET" class="mb-4" role="search">
                <div class="search-input position-relative">
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search questions">
                </div>
                @if ($specialty)<input type="hidden" name="specialty" value="{{ $specialty->slug }}">@endif
            </form>
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.08em">Topics</div>
            <nav class="d-grid gap-1">
                <a href="{{ route('questions.index') }}" class="chip justify-content-start {{ ! $specialty ? 'active' : '' }}">All topics</a>
                @foreach ($specialties as $s)
                    <a href="{{ route('questions.index', ['specialty' => $s->slug]) }}" class="chip justify-content-start {{ $specialty?->id === $s->id ? 'active' : '' }}">{{ $s->name }}</a>
                @endforeach
            </nav>
        </aside>
        <div class="col-lg-9">
            <div class="d-flex gap-2 mb-3">
                <a href="{{ request()->fullUrlWithQuery(['filter' => null, 'page' => null]) }}" class="chip {{ request('filter') !== 'unanswered' ? 'active' : '' }}">Latest</a>
                <a href="{{ request()->fullUrlWithQuery(['filter' => 'unanswered', 'page' => null]) }}" class="chip {{ request('filter') === 'unanswered' ? 'active' : '' }}">Awaiting answers</a>
            </div>
            @forelse ($questions as $question)
                <a href="{{ route('questions.show', $question) }}" class="qa-item">
                    <div class="small text-muted mb-1">{{ $question->specialty?->name ?? 'General' }} · {{ $question->askerLabel() }} · {{ $question->published_at->diffForHumans() }}</div>
                    <h3>{{ $question->title }}</h3>
                    <p class="line-clamp-2">{{ \Illuminate\Support\Str::limit($question->body, 200) }}</p>
                    <div class="d-flex flex-wrap gap-3 small mt-2">
                        <span class="fw-semibold {{ $question->answers_count ? 'text-forest' : 'text-muted' }}"><x-icon name="message-circle" size="sm" /> {{ $question->answers_count ? $question->answers_count.' '.\Illuminate\Support\Str::plural('answer', $question->answers_count) : 'No answers yet' }}</span>
                        @foreach ($question->conditions as $c)<span class="text-muted">#{{ $c->name }}</span>@endforeach
                    </div>
                </a>
            @empty
                <x-empty icon="help-circle" title="No questions here yet" text="Be the first to ask. A verified specialist will answer publicly." />
            @endforelse
            <div class="mt-4">{{ $questions->links() }}</div>
        </div>
    </div>
</div>
@endsection
