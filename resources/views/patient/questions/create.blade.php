@extends('layouts.dashboard')
@section('title', 'Ask a question')
@section('crumb', 'Ask a question')
@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('patient.questions.store') }}" class="card-soft p-4">
            @csrf
            <h1 class="h3">Ask a doctor, anonymously</h1>
            <p class="text-muted">Your name is never shown. Verified practitioners answer publicly so others can learn too.</p>
            <div class="mb-3">
                <label class="form-label" for="specialty_id">Topic</label>
                <select id="specialty_id" name="specialty_id" class="form-select @error('specialty_id') is-invalid @enderror" required>
                    <option value="">Choose the closest topic…</option>
                    @foreach ($specialties as $s)<option value="{{ $s->id }}" @selected(old('specialty_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
                <x-field-error name="specialty_id" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="title">Your question in one line</label>
                <input id="title" name="title" maxlength="180" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Is it normal for acne to come back after stopping treatment?" required>
                <x-field-error name="title" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="body">Details</label>
                <textarea id="body" name="body" rows="7" maxlength="3000" class="form-control @error('body') is-invalid @enderror" placeholder="How long has it been going on? What have you tried? Anything that makes it better or worse?" required>{{ old('body') }}</textarea>
                <x-field-error name="body" />
            </div>
            <div class="mb-3">
                <span class="form-label d-block">Related symptoms <span class="text-muted fw-normal">(up to 3)</span></span>
                <select name="conditions[]" class="form-select" multiple size="5">
                    @foreach ($conditions as $c)<option value="{{ $c->id }}" @selected(in_array($c->id, old('conditions', [])))>{{ $c->name }}</option>@endforeach
                </select>
                <div class="form-hint">Hold Ctrl / Cmd to pick more than one.</div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="share_profile" id="share_profile" value="1" @checked(old('share_profile', true))>
                <label class="form-check-label small" for="share_profile">Show my gender and age (e.g. "Female, 28") to help doctors answer. Never my name.</label>
            </div>
            <button class="btn btn-primary btn-lg">Post question</button>
        </form>
    </div>
    <div class="col-xl-4">
        <div class="card-soft p-4">
            <h2 class="h6">Before you post</h2>
            <ul class="small text-soft ps-3 mb-0 d-grid gap-2">
                <li>Don't include names, phone numbers or other identifying details.</li>
                <li>Answers are general guidance, not a diagnosis.</li>
                <li><strong class="text-danger">Emergency?</strong> Don't wait for an answer. Go to your nearest hospital.</li>
                <li>Questions are checked by our team before they appear (usually within a few hours).</li>
            </ul>
        </div>
    </div>
</div>
@endsection
