@extends('layouts.auth')
@section('title', 'Two-factor authentication')
@section('content')
    <div x-data="{ recovery: false }">
        <h1 class="h2 mb-2">One more step</h1>
        <p class="text-muted mb-4" x-show="!recovery">Enter the 6-digit code from your authenticator app.</p>
        <p class="text-muted mb-4" x-show="recovery" x-cloak>Enter one of your emergency recovery codes.</p>
        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <div class="mb-4" x-show="!recovery">
                <label for="code" class="form-label">Authentication code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control form-control-lg text-center fs-3 @error('code') is-invalid @enderror" style="letter-spacing:.4em" autofocus>
                <x-field-error name="code" />
            </div>
            <div class="mb-4" x-show="recovery" x-cloak>
                <label for="recovery_code" class="form-label">Recovery code</label>
                <input id="recovery_code" name="recovery_code" autocomplete="one-time-code" class="form-control form-control-lg">
                <x-field-error name="recovery_code" />
            </div>
            <button class="btn btn-primary btn-lg w-100">Continue</button>
        </form>
        <button type="button" class="btn btn-link w-100 mt-2" @click="recovery = !recovery" x-text="recovery ? 'Use an authentication code' : 'Use a recovery code instead'"></button>
    </div>
@endsection
