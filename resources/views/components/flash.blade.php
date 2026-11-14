@php
    $types = ['success' => ['success', 'check-circle'], 'error' => ['danger', 'x-circle'], 'warning' => ['warning', 'alert'], 'info' => ['info', 'info'], 'status' => ['success', 'check-circle']];
@endphp
@foreach ($types as $key => [$class, $icon])
    @if (session($key))
        <div class="alert alert-{{ $class }} d-flex gap-2 align-items-start" role="alert">
            <x-icon :name="$icon" class="mt-1" />
            <div>{{ session($key) === 'verification-link-sent' ? 'A new verification code has been sent to your email address.' : session($key) }}</div>
        </div>
    @endif
@endforeach
