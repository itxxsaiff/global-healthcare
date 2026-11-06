@extends('layouts.auth')
@section('title', 'Create a patient account')
@section('content')
    <a href="{{ route('register') }}" class="small text-muted d-inline-flex align-items-center gap-1 mb-3"><x-icon name="arrow-left" size="sm" /> Back</a>
    <h1 class="h2 mb-2">Create your account</h1>
    <p class="text-muted mb-4">Free, and it takes about a minute. Your details stay private.</p>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf
        <input type="hidden" name="role" value="patient">
        @include('auth._shared-fields', ['genders' => \App\Enums\Gender::forPatients()])

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="date_of_birth" class="form-label">Date of birth</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ now()->subYears(13)->toDateString() }}" class="form-control @error('date_of_birth') is-invalid @enderror" required>
                <x-field-error name="date_of_birth" />
            </div>
            <div class="col-sm-6">
                <label for="phone" class="form-label">Phone <span class="text-muted fw-normal">(optional)</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" autocomplete="tel" placeholder="+92 300 1234567">
                <x-field-error name="phone" />
            </div>
        </div>

        <div class="form-check mb-2">
            <input class="form-check-input @error('telehealth_consent') is-invalid @enderror" type="checkbox" name="telehealth_consent" id="telehealth_consent" value="1" @checked(old('telehealth_consent'))>
            <label class="form-check-label small" for="telehealth_consent">I understand online consultations are <strong>not for emergencies</strong>.</label>
            <x-field-error name="telehealth_consent" />
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1" @checked(old('terms'))>
            <label class="form-check-label small" for="terms">I agree to the <a href="{{ route('pages.show', 'terms') }}" target="_blank">Terms</a> and <a href="{{ route('pages.show', 'privacy') }}" target="_blank">Privacy Policy</a>.</label>
            <x-field-error name="terms" />
        </div>

        <button class="btn btn-primary btn-lg w-100">Create account</button>
    </form>
    <p class="text-center text-muted mt-4 mb-0">Already registered? <a href="{{ route('login') }}" class="fw-semibold">Log in</a></p>
@endsection
