@extends('layouts.auth')
@section('title', 'Reset your password')
@section('content')
    <h1 class="h2 mb-2">Forgot your password?</h1>
    <p class="text-muted mb-4">No problem. Enter your email and we'll send you a link to choose a new one.</p>
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus>
            <x-field-error name="email" />
        </div>
        <button class="btn btn-primary btn-lg w-100">Email me a reset link</button>
    </form>
    <p class="text-center mt-4 mb-0"><a href="{{ route('login') }}">Back to log in</a></p>
@endsection
