@extends('layouts.dashboard')
@section('title', 'Leave a review')
@section('crumb', 'Leave a review')
@section('content')
<div class="card-soft p-4 p-lg-5 mx-auto" style="max-width: 640px">
    <div class="d-flex gap-3 align-items-center mb-4">
        <x-avatar :profile="$booking->practitioner" size="md" />
        <div>
            <h1 class="h3 mb-0">How was your consultation?</h1>
            <div class="text-muted small">with {{ $booking->practitioner->displayName() }} on {{ $booking->startsAtFor(auth()->user()->timezone)->format('j M Y') }}</div>
        </div>
    </div>
    <form method="POST" action="{{ route('patient.reviews.store', $booking) }}">
        @csrf
        <fieldset class="mb-4">
            <legend class="form-label">Your rating</legend>
            <div class="star-input">
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" name="rating" id="r{{ $i }}" value="{{ $i }}" @checked(old('rating') == $i) required>
                    <label for="r{{ $i }}" title="{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="visually-hidden">{{ $i }} stars</span></label>
                @endfor
            </div>
            <x-field-error name="rating" />
        </fieldset>
        <div class="mb-4">
            <label for="body" class="form-label">Tell others about it <span class="text-muted fw-normal">(optional)</span></label>
            <textarea id="body" name="body" rows="5" maxlength="1500" class="form-control" placeholder="Was the practitioner on time? Did they listen? Was the advice clear?">{{ old('body') }}</textarea>
            <div class="form-hint">Shown as "{{ auth()->user()->firstName() }} {{ mb_substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1) }}." Please don't include medical details you'd rather keep private.</div>
        </div>
        <button class="btn btn-primary btn-lg">Submit review</button>
    </form>
</div>
@endsection
