@extends('layouts.auth')
@section('title', 'Confirm your password')
@section('content')
    <h1 class="h2 mb-2">Confirm it's you</h1>
    <p class="text-muted mb-4">This is a secure area. Please enter your password to continue.</p>
    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" required autofocus autocomplete="current-password">
            <x-field-error name="password" />
        </div>
        <button class="btn btn-primary btn-lg w-100">Confirm</button>
    </form>
@endsection
