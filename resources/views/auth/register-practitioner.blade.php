@extends('layouts.auth')
@section('title', 'Apply as a practitioner')
@section('side_eyebrow', 'How listing works')
@section('side')
    <ol class="list-unstyled d-grid gap-4 mb-0">
        <li class="d-flex gap-3"><span class="icon-tile sm round flex-shrink-0 fw-bold" style="background:rgba(141,229,208,.15);color:#8DE5D0">1</span><div><strong class="text-white d-block">Create your account</strong>Basic details now. Your full profile comes next.</div></li>
        <li class="d-flex gap-3"><span class="icon-tile sm round flex-shrink-0 fw-bold" style="background:rgba(141,229,208,.15);color:#8DE5D0">2</span><div><strong class="text-white d-block">Complete your profile & upload documents</strong>ID, degree and licence. Stored privately and seen only by our verification team.</div></li>
        <li class="d-flex gap-3"><span class="icon-tile sm round flex-shrink-0 fw-bold" style="background:rgba(141,229,208,.15);color:#8DE5D0">3</span><div><strong class="text-white d-block">We verify you</strong>Usually within 1–2 working days.</div></li>
        <li class="d-flex gap-3"><span class="icon-tile sm round flex-shrink-0 fw-bold" style="background:rgba(141,229,208,.15);color:#8DE5D0">4</span><div><strong class="text-white d-block">Pick a plan and go live</strong>Free with 20% commission, or a flat monthly subscription.</div></li>
    </ol>
@endsection
@section('content')
    <a href="{{ route('register') }}" class="small text-muted d-inline-flex align-items-center gap-1 mb-3"><x-icon name="arrow-left" size="sm" /> Back</a>
    <h1 class="h2 mb-2">Apply to list your practice</h1>
    <p class="text-muted mb-4">Start with the basics. You'll add qualifications, fees and documents on the next screens.</p>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf
        <input type="hidden" name="role" value="practitioner">
        @include('auth._shared-fields', [
            'genders' => \App\Enums\Gender::forPractitioners(),
            'namePlaceholder' => 'Your name as it appears on your licence',
            'countryLabel' => 'Country you practise in',
            'countryHint' => 'Pakistan-based practitioners see PKR plans; everyone else sees USD plans.',
        ])
        <div class="mb-3">
            <label for="phone" class="form-label">WhatsApp number <span class="text-muted fw-normal">(optional)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+92 300 1234567">
            <div class="form-hint">Only shown publicly on paid plans, and only if you choose.</div>
            <x-field-error name="phone" />
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1" @checked(old('terms'))>
            <label class="form-check-label small" for="terms">I agree to the <a href="{{ route('pages.show', 'terms') }}" target="_blank">Terms</a> and <a href="{{ route('pages.show', 'privacy') }}" target="_blank">Privacy Policy</a>, and confirm I hold a valid licence to practise.</label>
            <x-field-error name="terms" />
        </div>
        <button class="btn btn-primary btn-lg w-100">Create practitioner account</button>
    </form>
@endsection
