@extends('layouts.dashboard')
@section('title', 'Questions')
@section('crumb', 'Questions')
@section('content')
<h1 class="dash-title">Ask a Doctor moderation</h1>
<p class="text-muted mb-3">Publish general health questions; reject anything with personal details, spam or emergencies (point those to a hospital).</p>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'Waiting', 'approved' => 'Published', 'rejected' => 'Rejected']])
@forelse ($questions as $q)
    <div class="card-soft p-4 mb-3">
        <div class="small text-muted mb-1">{{ $q->specialty?->name }} · from {{ $q->patient->name }} (shown as "{{ $q->askerLabel() }}") · {{ $q->created_at->diffForHumans() }}</div>
        <form method="POST" action="{{ route('admin.questions.update', $q) }}">
            @csrf @method('PATCH')
            <input name="title" value="{{ $q->title }}" class="form-control fw-semibold mb-2">
            <p class="text-soft" style="white-space: pre-line">{{ $q->body }}</p>
            <div class="d-flex gap-2">
                <button name="status" value="approved" class="btn btn-sm btn-primary">{{ $q->status === \App\Enums\ModerationStatus::Approved ? 'Save title' : 'Publish' }}</button>
                @if ($q->status !== \App\Enums\ModerationStatus::Rejected)<button name="status" value="rejected" class="btn btn-sm btn-paper">Reject</button>@endif
            </div>
        </form>
        @if ($q->answers->isNotEmpty())
            <div class="border-top mt-3 pt-3">
                <div class="small fw-bold mb-2">Answers</div>
                @foreach ($q->answers as $a)
                    <div class="d-flex justify-content-between gap-3 small py-2 border-bottom">
                        <div><strong>{{ $a->practitioner->displayName() }}</strong>: {{ \Illuminate\Support\Str::limit(strip_tags($a->body), 160) }}</div>
                        <form method="POST" action="{{ route('admin.answers.update', $a) }}">@csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $a->status === \App\Enums\ModerationStatus::Approved ? 'rejected' : 'approved' }}">
                            <button class="btn btn-sm btn-link p-0">{{ $a->status === \App\Enums\ModerationStatus::Approved ? 'Hide' : 'Restore' }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <x-empty icon="help-circle" title="Nothing waiting" />
@endforelse
{{ $questions->links() }}
@endsection
