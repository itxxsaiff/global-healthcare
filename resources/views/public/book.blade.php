@extends('layouts.app')
@section('title', 'Book '.$profile->displayName())
@section('noindex', true)
@section('hide_whatsapp', true)
@php
    $direct = (bool) $profile->activeMembership?->plan?->direct_dealing;
    $firstDay = array_key_first($days);
@endphp
@section('content')
<div class="container py-4 py-lg-5" style="max-width: 1040px">
    <a href="{{ route('practitioners.show', $profile) }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Back to profile</a>

    <div class="row g-4">
        <div class="col-lg-8">
            <h1 class="h2 mb-1">Choose a time</h1>
            <p class="text-muted">Times are shown in <strong class="text-ink">{{ str_replace('_', ' ', $tz) }}</strong> time.@guest <a href="{{ route('login') }}">Log in</a> to use your saved time zone.@endguest</p>

            @if (empty($days))
                <x-empty icon="calendar" title="No open slots in the next few weeks" text="{{ $profile->user->firstName() }} hasn't published availability yet, or is fully booked. Ask us on WhatsApp and we'll help you find a time." />
            @else
            <form method="POST" action="{{ route('booking.store', $profile->slug) }}" x-data="{ day: '{{ $firstDay }}', slot: '{{ old('slot') }}' }" class="card-soft p-4">
                @csrf
                <div class="day-strip mb-4" role="tablist" aria-label="Choose a day">
                    @foreach ($days as $date => $slots)
                        @php $d = \Carbon\Carbon::parse($date); @endphp
                        <button type="button" role="tab" :class="{ selected: day === '{{ $date }}' }" @click="day = '{{ $date }}'; slot = ''">
                            <small>{{ $d->format('D') }}</small>
                            <strong>{{ $d->format('j') }}</strong>
                            <small>{{ $d->format('M') }}</small>
                        </button>
                    @endforeach
                </div>

                @foreach ($days as $date => $slots)
                    <div x-show="day === '{{ $date }}'" x-cloak>
                        <div class="small fw-semibold text-muted mb-2">{{ \Carbon\Carbon::parse($date)->format('l j F') }} · {{ count($slots) }} {{ \Illuminate\Support\Str::plural('time', count($slots)) }} available</div>
                        <div class="slot-grid">
                            @foreach ($slots as $s)
                                <button type="button" :class="{ selected: slot === '{{ $s['start'] }}' }" @click="slot = '{{ $s['start'] }}'">{{ $s['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <input type="hidden" name="slot" :value="slot">
                <x-field-error name="slot" />

                <div class="mt-4">
                    <label for="note" class="form-label">Anything {{ $profile->user->firstName() }} should know beforehand? <span class="text-muted fw-normal">(optional, private)</span></label>
                    <textarea id="note" name="note" rows="3" maxlength="1000" class="form-control" placeholder="e.g. main concern, how long it's been going on, current medicines">{{ old('note') }}</textarea>
                    <div class="form-hint">Only {{ $profile->user->firstName() }} can see this. Stored encrypted.</div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" id="consent" value="1">
                    <label class="form-check-label small" for="consent">I understand this is a non-emergency online consultation.</label>
                    <x-field-error name="consent" />
                </div>

                @auth
                    @if (auth()->user()->isPatient())
                        <button class="btn btn-primary btn-lg w-100 mt-4" :disabled="!slot">Send booking request</button>
                    @else
                        <div class="alert alert-light border mt-4 mb-0 small">You're logged in as a {{ auth()->user()->role->value }}. Use a patient account to book.</div>
                    @endif
                @else
                    <a href="{{ route('register.patient') }}" class="btn btn-primary btn-lg w-100 mt-4">Create a free account to book</a>
                    <p class="text-center small text-muted mt-2 mb-0">Already have one? <a href="{{ route('login') }}">Log in</a></p>
                @endauth
            </form>
            @endif
        </div>

        <aside class="col-lg-4">
            <div class="card-soft p-4 profile-aside">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <x-avatar :profile="$profile" size="md" />
                    <div>
                        <div class="fw-bold">{{ $profile->displayName() }}</div>
                        <div class="small text-forest fw-semibold">{{ $profile->primarySpecialty?->practitioner_label }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between py-2 border-top small"><span class="text-muted">Session</span><span>{{ $profile->session_minutes }} min, online</span></div>
                <div class="d-flex justify-content-between py-2 border-top small"><span class="text-muted">Fee</span><strong>{{ $profile->feeLabel() }}</strong></div>
                <div class="border-top pt-3 small">
                    <strong class="d-block mb-2">What happens next</strong>
                    <ol class="ps-3 mb-0 text-soft d-grid gap-1">
                        <li>You send a request. No payment yet.</li>
                        <li>{{ $profile->user->firstName() }} accepts (usually within a few hours).</li>
                        @if ($direct)
                            <li>Your booking is confirmed. Payment is arranged directly with {{ $profile->user->firstName() }}.</li>
                        @else
                            <li>We email you a secure payment link.</li>
                            <li>Once paid, your booking is confirmed.</li>
                        @endif
                    </ol>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
