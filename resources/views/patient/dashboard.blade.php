@extends('layouts.dashboard')
@section('title', 'My dashboard')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Hi {{ $user->firstName() }}</h1>
        <p class="text-muted mb-0">Your appointments and anything that needs your attention.</p>
    </div>
    <a href="{{ route('directory') }}" class="btn btn-primary"><x-icon name="search" /> Find a practitioner</a>
</div>

@foreach ($needsPayment as $b)
    <div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div><strong>{{ $b->practitioner->displayName() }} accepted your request.</strong> Please pay {{ $b->feeLabel() }} by {{ $b->payment_due_at->setTimezone($user->timezone)->format('D j M, g:i A') }} to confirm.</div>
        @if (Route::has('patient.bookings.show'))<a href="{{ route('patient.bookings.show', $b) }}" class="btn btn-clay btn-sm">Pay now</a>@endif
    </div>
@endforeach

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Upcoming</h2>
            @forelse ($upcoming as $b)
                <div class="d-flex gap-3 align-items-center py-3 border-bottom">
                    <x-avatar :profile="$b->practitioner" size="md" />
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $b->practitioner->displayName() }}</div>
                        <div class="small text-muted">{{ $b->practitioner->primarySpecialty?->practitioner_label }} · {{ $b->startsAtFor($user->timezone)->format('D j M, g:i A') }}</div>
                    </div>
                    <x-status :status="$b->status" />
                </div>
            @empty
                <x-empty icon="calendar" title="No upcoming appointments" text="When you book a consultation, it'll show up here with everything you need to join." class="border-0 bg-transparent py-4">
                    <a href="{{ route('directory') }}" class="btn btn-paper">Browse practitioners</a>
                </x-empty>
            @endforelse
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-2">Not sure who to see?</h2>
            <p class="small text-muted">Start with a symptom and we'll suggest the right kind of specialist.</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach (\App\Models\Condition::query()->active()->where('is_featured', true)->limit(8)->get() as $c)
                    <a href="{{ route('conditions.show', $c) }}" class="chip">{{ $c->name }}</a>
                @endforeach
            </div>
        </div>
        @if ($toReview->isNotEmpty())
            <div class="card-soft p-4">
                <h2 class="h5 mb-2">How did it go?</h2>
                <p class="small text-muted">Your review helps other patients choose with confidence.</p>
                @foreach ($toReview as $b)
                    <div class="d-flex justify-content-between align-items-center py-2 border-top">
                        <span>{{ $b->practitioner->displayName() }}</span>
                        @if (Route::has('patient.reviews.create'))<a href="{{ route('patient.reviews.create', $b) }}" class="btn btn-sm btn-paper">Leave a review</a>@endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
