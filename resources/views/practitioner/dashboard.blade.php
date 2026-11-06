@extends('layouts.dashboard')
@section('title', 'Dashboard')
@php
    $status = $profile->verification_status;
    $done = collect($checklist)->where('done', true)->count();
    $total = count($checklist);
@endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Hello, {{ auth()->user()->firstName() }}</h1>
        <p class="text-muted mb-0">
            @if ($profile->is_public)
                Your profile is live. <a href="{{ route('practitioners.show', $profile) }}" target="_blank">View public profile <x-icon name="external" size="sm" /></a>
            @else
                Your profile isn't public yet. Here's what's left.
            @endif
        </p>
    </div>
    <x-status :status="$status" />
</div>

@if (! $profile->is_public)
    <div class="card-soft p-4 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                @switch($status)
                    @case(\App\Enums\VerificationStatus::Pending)
                        <h2 class="h4">We're checking your documents</h2>
                        <p class="text-muted mb-0">Thanks for submitting. Our team usually verifies new practitioners within 1–2 working days. We'll email you as soon as it's done, and then you can choose a plan and go live.</p>
                        @break
                    @case(\App\Enums\VerificationStatus::Rejected)
                        <h2 class="h4">A few changes needed</h2>
                        <div class="alert alert-warning small mb-3">{{ $profile->rejection_reason }}</div>
                        @if (Route::has('practitioner.onboarding.show'))<a href="{{ route('practitioner.onboarding.show') }}" class="btn btn-primary">Update and resubmit</a>@endif
                        @break
                    @case(\App\Enums\VerificationStatus::Approved)
                        <h2 class="h4">You're verified. Choose a plan to go live</h2>
                        <p class="text-muted">Pick the plan that suits you. Your profile becomes public as soon as it's active.</p>
                        @if (Route::has('practitioner.membership.show'))<a href="{{ route('practitioner.membership.show') }}" class="btn btn-primary">Choose a plan</a>@endif
                        @break
                    @default
                        <h2 class="h4">Complete your profile</h2>
                        <p class="text-muted">Patients only see verified practitioners. Finish these steps and submit your profile for review.</p>
                        <div class="progress mb-2" style="height:8px" role="progressbar" aria-valuenow="{{ $done }}" aria-valuemin="0" aria-valuemax="{{ $total }}"><div class="progress-bar bg-success" style="width: {{ $done / $total * 100 }}%"></div></div>
                        <small class="text-muted">{{ $done }} of {{ $total }} done</small>
                @endswitch
            </div>
            <div class="col-lg-7">
                <ul class="list-unstyled d-grid gap-2 mb-0">
                    @foreach ($checklist as $item)
                        <li class="d-flex align-items-center gap-3 p-2 rounded-3 {{ $item['done'] ? '' : 'bg-paper' }}">
                            <x-icon :name="$item['done'] ? 'check-circle' : 'circle-dot'" class="{{ $item['done'] ? 'text-forest' : 'text-muted' }}" />
                            <span class="flex-grow-1 {{ $item['done'] ? 'text-muted text-decoration-line-through' : 'fw-semibold' }}">{{ $item['label'] }}</span>
                            @if (! $item['done'] && $status->isEditable() && Route::has('practitioner.onboarding.show'))
                                <a href="{{ route('practitioner.onboarding.show', ['step' => $item['step']]) }}" class="btn btn-sm btn-paper">Do this</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Requests to answer</div><div class="stat-value">{{ $requests->count() }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Upcoming sessions</div><div class="stat-value">{{ $upcoming->count() }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Completed sessions</div><div class="stat-value">{{ $stats['completed'] }}</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Rating</div><div class="stat-value">{{ $profile->reviews_count ? number_format($profile->rating_avg, 1) : '—' }}</div><div class="stat-note">{{ $profile->reviews_count }} reviews</div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">New booking requests</h2>
            @forelse ($requests as $b)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">{{ $b->patient->name }}</div>
                        <div class="small text-muted">{{ $b->startsAtFor($profile->timezone)->format('D j M, g:i A') }} · reply by {{ $b->respond_by?->setTimezone($profile->timezone)->format('D g:i A') }}</div>
                    </div>
                    @if (Route::has('practitioner.bookings.show'))<a href="{{ route('practitioner.bookings.show', $b) }}" class="btn btn-sm btn-primary">Review</a>@endif
                </div>
            @empty
                <p class="text-muted mb-0">No new requests right now.</p>
            @endforelse
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Coming up</h2>
            @forelse ($upcoming as $b)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">{{ $b->patient->name }}</div>
                        <div class="small text-muted">{{ $b->startsAtFor($profile->timezone)->format('D j M, g:i A') }}</div>
                    </div>
                    <x-status :status="$b->status" />
                </div>
            @empty
                <p class="text-muted mb-0">Nothing scheduled yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
