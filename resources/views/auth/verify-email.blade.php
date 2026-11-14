@extends('layouts.auth')
@section('title', 'Verify your email')
@inject('codes', 'App\Services\Auth\EmailCodeService')
@php
    $user = auth()->user();
    $current = $codes->current($user);
    $wait = $codes->secondsUntilResend($user);
    $expired = $current && $current->expires_at->isPast();
@endphp
@section('content')
    <div class="mb-3" style="width:56px;height:56px;border-radius:16px;background:#E3F4EF;color:#0E8474;display:grid;place-items:center"><x-icon name="mail" size="lg" /></div>
    <h1 class="h2 mb-2">Check your inbox</h1>

    @if ($current)
        <p class="text-muted">We've emailed a 6-digit code to <strong class="text-ink">{{ $user->email }}</strong>. Enter it below to confirm it's you. It can take a minute to arrive, so check your spam folder too.</p>
    @else
        <p class="text-muted">To finish setting up your account, we need to confirm <strong class="text-ink">{{ $user->email }}</strong> is yours. Send yourself a 6-digit code and enter it here.</p>
    @endif

    @if ($current)
        <form method="POST" action="{{ route('verification.code') }}" class="mt-4" data-otp-form>
            @csrf
            <label for="code" class="form-label">Verification code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" pattern="\d{3}\s?\d{3}"
                   placeholder="000000" required autofocus data-otp
                   class="form-control form-control-lg text-center fs-3 otp-input @error('code') is-invalid @enderror">
            <x-field-error name="code" />
            <div class="form-hint">
                @if ($expired)
                    This code has expired. Send yourself a new one.
                @else
                    The code expires at {{ $current->expires_at->timezone($user->timezone ?: config('app.timezone'))->format('g:i a') }} ({{ $codes->minutes() }} minutes after it was sent).
                @endif
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-3">Confirm email</button>
        </form>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <form method="POST" action="{{ route('verification.code.resend') }}">
            @csrf
            <button class="btn {{ $current ? 'btn-paper' : 'btn-primary' }}" data-resend-wait="{{ $wait }}" data-label="{{ $current ? 'Send a new code' : 'Send me a code' }}" @disabled($wait > 0)>
                {{ $current ? 'Send a new code' : 'Send me a code' }}@if ($wait > 0) <span class="text-muted">in {{ $wait }}s</span>@endif
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="ms-auto">
            @csrf
            <button class="btn btn-link text-muted px-0">Wrong email? Log out</button>
        </form>
    </div>
@endsection
