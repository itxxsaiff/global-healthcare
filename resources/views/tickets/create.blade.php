@extends('layouts.dashboard')
@section('title', 'Report an issue')
@section('crumb', 'Report an issue')
@section('content')
<form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="card-soft p-4 mx-auto" style="max-width: 720px">
    @csrf
    <h1 class="h3">Report an issue</h1>
    <p class="text-muted">Tell us what happened. A real person on our team reads every report.</p>
    <span class="form-label d-block">What's it about?</span>
    <div class="row g-2 mb-3">
        @foreach ($categories as $c)
            <div class="col-sm-6">
                <label class="choice-card py-2 px-3 h-100">
                    <input type="radio" name="category" value="{{ $c->value }}" @checked(old('category') === $c->value) required>
                    <span class="fw-semibold">{{ $c->label() }}</span>
                </label>
            </div>
        @endforeach
    </div>
    <x-field-error name="category" />
    @if ($bookings->isNotEmpty())
        <div class="mb-3">
            <label class="form-label" for="booking">Related booking <span class="text-muted fw-normal">(optional)</span></label>
            <select id="booking" name="booking" class="form-select">
                <option value="">Not about a specific booking</option>
                @foreach ($bookings as $b)
                    <option value="{{ $b->reference }}" @selected(old('booking', $selected) === $b->reference)>{{ $b->reference }} · {{ $b->startsAtFor(auth()->user()->timezone)->format('j M Y, g:i A') }} · {{ $b->status->label() }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="mb-3">
        <label class="form-label" for="subject">Subject</label>
        <input id="subject" name="subject" maxlength="180" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" required>
        <x-field-error name="subject" />
    </div>
    <div class="mb-3">
        <label class="form-label" for="body">What happened?</label>
        <textarea id="body" name="body" rows="6" maxlength="5000" class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
        <x-field-error name="body" />
    </div>
    <div class="mb-4">
        <label class="form-label" for="attachment">Screenshot or receipt <span class="text-muted fw-normal">(optional, PDF/JPG/PNG, 3 MB)</span></label>
        <input id="attachment" type="file" name="attachment" accept="application/pdf,image/jpeg,image/png" class="form-control" data-max-kb="3072" data-max-message="File size exceeds 3MB. Please upload a compressed document to ensure fast processing">
        <div class="invalid-feedback js-file-error"></div>
        <x-field-error name="attachment" />
    </div>
    <button class="btn btn-primary btn-lg">Send report</button>
</form>
@endsection
