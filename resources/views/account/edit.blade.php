@extends('layouts.dashboard')
@section('title', 'Account & security')
@section('crumb', 'Account & security')
@section('content')
<h1 class="dash-title">Account & security</h1>
<p class="text-muted mb-4">Your login details, time zone and two-factor authentication.</p>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-3">Your details</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name', 'updateProfileInformation') is-invalid @enderror">
                    @error('name', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email', 'updateProfileInformation') is-invalid @enderror">
                    <div class="form-hint">Changing your email means verifying the new address.</div>
                    @error('email', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="timezone">Time zone</label>
                    <select id="timezone" name="timezone" class="form-select">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ str_replace('_', ' ', $tz) }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Appointment times are shown in this time zone.</div>
                </div>
                <button class="btn btn-primary">Save details</button>
                @if (session('status') === 'profile-information-updated')<span class="text-success small ms-2">Saved.</span>@endif
            </form>
        </div>

        <div class="card-soft p-4">
            <h2 class="h5 mb-3">Change password</h2>
            <form method="POST" action="{{ route('user-password.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                    @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="form-label" for="password">New password</label>
                        <input id="password" type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                        @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <button class="btn btn-primary">Update password</button>
                @if (session('status') === 'password-updated')<span class="text-success small ms-2">Password updated.</span>@endif
            </form>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card-soft p-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h2 class="h5 mb-0">Two-factor authentication</h2>
                @if ($user->two_factor_confirmed_at)
                    <span class="status tone-success">On</span>
                @else
                    <span class="status tone-neutral">Off</span>
                @endif
            </div>
            <p class="small text-muted">Adds a 6-digit code from an authenticator app (Google Authenticator, Authy, 1Password) when you log in.{{ $user->isAdmin() ? ' Required for admin accounts.' : '' }}</p>

            @if (! $user->two_factor_secret)
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="btn btn-primary"><x-icon name="key" /> Turn on</button>
                </form>
            @else
                @if (! $user->two_factor_confirmed_at)
                    <p class="small fw-semibold mb-2">Scan this QR code with your authenticator app, then enter the code it shows.</p>
                    <div class="bg-white border rounded-3 p-3 d-inline-block mb-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror" placeholder="123456" style="max-width:10rem">
                        <button class="btn btn-primary">Confirm</button>
                    </form>
                    @error('code', 'confirmTwoFactorAuthentication')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                @else
                    <p class="small fw-semibold mb-2">Recovery codes</p>
                    <p class="small text-muted">Store these somewhere safe. Each can be used once if you lose your phone.</p>
                    <div class="bg-paper border rounded-3 p-3 font-monospace small mb-3">
                        @foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code)<div>{{ $code }}</div>@endforeach
                    </div>
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="d-inline">
                        @csrf
                        <button class="btn btn-paper btn-sm"><x-icon name="refresh" /> New codes</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('two-factor.disable') }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-link text-danger btn-sm">Turn off</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
