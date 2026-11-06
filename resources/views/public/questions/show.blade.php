@extends('layouts.app')
@section('title', $question->title)
@section('description', \Illuminate\Support\Str::limit($question->body, 155))
@section('content')
<div class="container container-narrow py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('questions.index') }}">Ask a doctor</a></li>
            @if ($question->specialty)<li class="breadcrumb-item"><a href="{{ route('questions.index', ['specialty' => $question->specialty->slug]) }}">{{ $question->specialty->name }}</a></li>@endif
        </ol>
    </nav>

    <article>
        <h1 class="mb-3" style="font-size: clamp(1.7rem, 3.4vw, 2.4rem)">{{ $question->title }}</h1>
        <div class="small text-muted mb-4">{{ $question->askerLabel() }} · Asked {{ $question->published_at->format('j M Y') }} · {{ number_format($question->views) }} views</div>
        <div class="card-soft p-4 mb-5 fs-5" style="line-height:1.7">{!! nl2br(e($question->body)) !!}</div>

        <h2 class="h4 mb-3">{{ $answers->count() ? $answers->count().' '.\Illuminate\Support\Str::plural('answer', $answers->count()).' from verified practitioners' : 'Awaiting an answer' }}</h2>

        @foreach ($answers as $answer)
            @php $doc = $answer->practitioner; @endphp
            <div class="card-soft p-4 mb-4" id="answer-{{ $answer->id }}">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <x-avatar :profile="$doc" size="md" />
                    <div class="flex-grow-1">
                        <a href="{{ route('practitioners.show', $doc) }}" class="fw-bold text-ink">{{ $doc->displayName() }}</a> <span class="verified-tick"><x-icon name="badge-check" /></span>
                        <div class="small text-forest fw-semibold">{{ $doc->primarySpecialty?->practitioner_label }}</div>
                    </div>
                    <small class="text-muted">{{ $answer->created_at->format('j M Y') }}</small>
                </div>
                <div class="prose fs-6">{!! $answer->body !!}</div>
                @if ($doc->is_public)
                    <div class="border-top pt-3 mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="small text-muted">Want personal advice from {{ $doc->user->firstName() }}?</span>
                        <a href="{{ route('practitioners.show', $doc) }}#book" class="btn btn-primary btn-sm">Book a consultation</a>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="alert alert-light border small d-flex gap-2"><x-icon name="info" class="mt-1" /><div>Answers are general information from verified practitioners and are not a diagnosis. In an emergency, contact your local emergency services.</div></div>
    </article>

    @if ($related->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-2">Related questions</h2>
            @foreach ($related as $r)
                <a href="{{ route('questions.show', $r) }}" class="qa-item py-3"><h3 class="mb-0">{{ $r->title }}</h3></a>
            @endforeach
        </section>
    @endif
</div>
@endsection

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'QAPage',
    'mainEntity' => array_filter([
        '@type' => 'Question',
        'name' => $question->title,
        'text' => $question->body,
        'answerCount' => $answers->count(),
        'dateCreated' => $question->published_at->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => 'Anonymous'],
        'suggestedAnswer' => $answers->map(fn ($a) => [
            '@type' => 'Answer',
            'text' => strip_tags($a->body),
            'dateCreated' => $a->created_at->toIso8601String(),
            'url' => route('questions.show', $question).'#answer-'.$a->id,
            'author' => ['@type' => 'Person', 'name' => $a->practitioner->displayName(), 'url' => route('practitioners.show', $a->practitioner)],
        ])->values()->all() ?: null,
    ]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
