@extends('layouts.auth')
@section('title', 'Log in')
@section('content')
    <h1 class="h2 mb-2">Welcome back</h1>
    <p class="text-muted mb-4">Log in to manage your bookings, profile and messages.</p>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            <x-field-error name="email" />
        </div>
        <div class="mb-2">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label">Password</label>
                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
            </div>
            <input id="password" type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" required autocomplete="current-password">
            <x-field-error name="password" />
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Keep me logged in on this device</label>
        </div>
        <button class="btn btn-primary btn-lg w-100">Log in</button>
    </form>

    <p class="text-center text-muted mt-4 mb-0">New here? <a href="{{ route('register') }}" class="fw-semibold">Create a free account</a></p>
@endsection
