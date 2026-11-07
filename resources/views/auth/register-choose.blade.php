@extends('layouts.auth')
@section('title', 'Create an account')
@section('content')
    <h1 class="h2 mb-2">Let's get you set up</h1>
    <p class="text-muted mb-4">Tell us who you are and we'll show you the right sign-up form.</p>

    <div class="d-grid gap-3">
        <a href="{{ route('register.patient') }}" class="choice-card text-decoration-none text-reset d-flex gap-3 align-items-center">
            <span class="tile-icon d-grid rounded-3 flex-shrink-0" style="width:52px;height:52px;place-items:center;background:#E3F4EF;color:#0E8474"><x-icon name="heart" size="lg" /></span>
            <span class="flex-grow-1">
                <strong class="d-block fs-5">I'm looking for care</strong>
                <span class="text-muted">Book online consultations, ask questions and leave reviews.</span>
            </span>
            <x-icon name="chevron-right" class="text-muted" />
        </a>
        <a href="{{ route('register.practitioner') }}" class="choice-card text-decoration-none text-reset d-flex gap-3 align-items-center">
            <span class="tile-icon d-grid rounded-3 flex-shrink-0" style="width:52px;height:52px;place-items:center;background:#FCE7E1;color:#9A4426"><x-icon name="stethoscope" size="lg" /></span>
            <span class="flex-grow-1">
                <strong class="d-block fs-5">I'm a practitioner</strong>
                <span class="text-muted">Doctors, psychologists, dietitians and therapists. List your practice after verification.</span>
            </span>
            <x-icon name="chevron-right" class="text-muted" />
        </a>
    </div>

    <p class="text-center text-muted mt-4 mb-0">Already have an account? <a href="{{ route('login') }}" class="fw-semibold">Log in</a></p>
@endsection
