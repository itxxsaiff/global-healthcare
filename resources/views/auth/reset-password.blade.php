@extends('layouts.auth')
@section('title', 'Choose a new password')
@section('content')
    <h1 class="h2 mb-4">Choose a new password</h1>
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-control @error('email') is-invalid @enderror" required>
            <x-field-error name="email" />
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
            <x-field-error name="password" />
            <div class="form-hint" data-password-hint>At least 8 characters, with letters and numbers.</div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password" data-confirms="password">
            <div class="form-hint" data-confirm-hint hidden></div>
        </div>
        <button class="btn btn-primary btn-lg w-100">Save new password</button>
    </form>
@endsection
