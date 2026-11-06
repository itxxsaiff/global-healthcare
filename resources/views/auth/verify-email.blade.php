@extends('layouts.auth')
@section('title', 'Verify your email')
@section('content')
    <div class="empty-icon mb-3" style="width:56px;height:56px;border-radius:16px;background:#E3ECE5;color:#1F5C4A;display:grid;place-items:center"><x-icon name="mail" size="lg" /></div>
    <h1 class="h2 mb-2">Check your inbox</h1>
    <p class="text-muted">We've sent a verification link to <strong class="text-ink">{{ auth()->user()->email }}</strong>. Click it to activate your account. It can take a minute or two to arrive, and it's worth checking your spam folder.</p>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary">Resend the email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-paper">Log out</button>
        </form>
    </div>
@endsection
