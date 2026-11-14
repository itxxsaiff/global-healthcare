@extends('layouts.dashboard')
@section('title', 'My profile')
@section('crumb', 'My profile')
@php
    $status = $profile->verification_status;
    $stepKeys = array_keys($steps);
    $stepDone = ['details' => $checklist['details']['done'], 'focus' => $checklist['focus']['done'], 'about' => $checklist['bio']['done'] && $checklist['photo']['done'], 'documents' => $checklist['documents']['done'], 'review' => false];
    $docMb = round(\App\Models\Setting::get('document_max_kb', 3072) / 1024);
@endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="dash-title">{{ $status === \App\Enums\VerificationStatus::Draft ? 'Set up your profile' : 'My profile' }}</h1>
        <p class="text-muted mb-0">This is what patients see once you're verified. Clear, warm and specific works best.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <x-status :status="$status" />
        <a href="{{ route('practitioners.show', $profile) }}" target="_blank" class="btn btn-paper btn-sm"><x-icon name="eye" /> Preview</a>
    </div>
</div>

@if ($status === \App\Enums\VerificationStatus::Rejected)
    <div class="alert alert-warning"><strong>Changes requested:</strong> {{ $profile->rejection_reason }}</div>
@elseif ($status === \App\Enums\VerificationStatus::Pending)
    <div class="alert alert-info">Your profile is with our verification team, so editing is paused for now. We'll email you when it's reviewed.</div>
@elseif ($status === \App\Enums\VerificationStatus::Approved)
    <div class="alert alert-light border small"><x-icon name="info" /> You're verified. Changing your <strong>licence number or documents</strong> (or your name in Account settings) will hide your profile until our team re-checks it.</div>
@endif

<nav class="wizard-steps mb-4" aria-label="Profile steps">
    @foreach ($steps as $key => $label)
        <a href="{{ route('practitioner.onboarding.show', ['step' => $key]) }}" class="{{ $step === $key ? 'current' : ($stepDone[$key] ? 'done' : '') }}">
            @if ($stepDone[$key] && $step !== $key)<x-icon name="check" size="sm" />@else<span>{{ $loop->iteration }}</span>@endif
            {{ $label }}
        </a>
    @endforeach
</nav>

@if ($errors->any())
    <div class="alert alert-danger">Please fix the highlighted fields below.</div>
@endif

<fieldset @disabled(! $editable)>
@switch($step)

{{-- ───────── Step 1: details ───────── --}}
@case('details')
<form method="POST" action="{{ route('practitioner.onboarding.details') }}" class="card-soft p-4">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-sm-3">
            <label class="form-label" for="title">Title</label>
            <select id="title" name="title" class="form-select">
                <option value="">None</option>
                @foreach (['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'] as $t)<option @selected(old('title', $profile->title) === $t)>{{ $t }}</option>@endforeach
            </select>
        </div>
        <div class="col-sm-9">
            <span class="form-label d-block">Gender <span class="text-muted fw-normal">(patients can filter by this)</span></span>
            <div class="segmented">
                @foreach (\App\Enums\Gender::forPractitioners() as $g)
                    <input type="radio" name="gender" id="g-{{ $g->value }}" value="{{ $g->value }}" @checked(old('gender', $profile->gender?->value) === $g->value)>
                    <label for="g-{{ $g->value }}">{{ $g->label() }}</label>
                @endforeach
            </div>
            <x-field-error name="gender" />
        </div>

        <div class="col-md-6">
            <label class="form-label" for="primary_specialty_id">Main specialty</label>
            <select id="primary_specialty_id" name="primary_specialty_id" class="form-select @error('primary_specialty_id') is-invalid @enderror">
                <option value="">Choose…</option>
                @foreach ($specialties as $s)<option value="{{ $s->id }}" @selected(old('primary_specialty_id', $profile->primary_specialty_id) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
            <div class="form-hint">Only non-emergency outpatient specialties are listed.</div>
            <x-field-error name="primary_specialty_id" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="years_experience">Years of experience</label>
            <input id="years_experience" type="number" min="0" max="60" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" class="form-control @error('years_experience') is-invalid @enderror">
            <x-field-error name="years_experience" />
        </div>

        <div class="col-12">
            <label class="form-label" for="qualifications">Qualifications</label>
            <input id="qualifications" name="qualifications" value="{{ old('qualifications', $profile->qualifications) }}" class="form-control @error('qualifications') is-invalid @enderror" placeholder="e.g. MBBS, FCPS (Dermatology), MRCP (UK)">
            <x-field-error name="qualifications" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="license_number">Licence / registration number</label>
            <input id="license_number" name="license_number" value="{{ old('license_number', $profile->license_number) }}" class="form-control @error('license_number') is-invalid @enderror" placeholder="e.g. PMDC 12345-P">
            <div class="form-hint">Checked against your uploaded certificate. Not shown publicly.</div>
            <x-field-error name="license_number" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="licensing_body">Licensing body</label>
            <input id="licensing_body" name="licensing_body" value="{{ old('licensing_body', $profile->licensing_body) }}" class="form-control @error('licensing_body') is-invalid @enderror" placeholder="e.g. Pakistan Medical & Dental Council">
            <x-field-error name="licensing_body" />
        </div>

        <div class="col-md-4">
            <label class="form-label">Country</label>
            <input class="form-control" value="{{ $profile->country?->name }}" disabled>
            <div class="form-hint">Sets your plan currency. Contact support to change it.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="city_id">City</label>
            <select id="city_id" name="city_id" class="form-select">
                <option value="">Prefer not to say</option>
                @foreach ($profile->country?->cities ?? [] as $c)<option value="{{ $c->id }}" @selected(old('city_id', $profile->city_id) == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="timezone">Your time zone</label>
            <select id="timezone" name="timezone" class="form-select">
                @foreach ($timezones as $tz)<option value="{{ $tz }}" @selected(old('timezone', $profile->timezone) === $tz)>{{ str_replace('_', ' ', $tz) }}</option>@endforeach
            </select>
            <div class="form-hint">Your availability is set in this time zone.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="fee">Fee per session ({{ $profile->fee_currency }})</label>
            <div class="input-group">
                <span class="input-group-text">{{ $profile->fee_currency === 'PKR' ? 'Rs' : '$' }}</span>
                <input id="fee" type="number" min="1" step="1" name="fee" value="{{ old('fee', $profile->fee_minor ? $profile->fee_minor / 100 : '') }}" class="form-control @error('fee') is-invalid @enderror">
            </div>
            <x-field-error name="fee" />
        </div>
        <div class="col-md-6">
            <span class="form-label d-block">Session length</span>
            <div class="segmented">
                @foreach ([20, 30, 45, 60] as $m)
                    <input type="radio" name="session_minutes" id="sm-{{ $m }}" value="{{ $m }}" @checked((int) old('session_minutes', $profile->session_minutes) === $m)>
                    <label for="sm-{{ $m }}">{{ $m }} min</label>
                @endforeach
            </div>
        </div>

        <div class="col-12"><hr class="my-2"></div>
        <div class="col-md-6">
            <label class="form-label" for="linkedin_url">LinkedIn <span class="text-muted fw-normal">(optional)</span></label>
            <input id="linkedin_url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" class="form-control @error('linkedin_url') is-invalid @enderror" placeholder="https://linkedin.com/in/…">
            <div class="form-hint">Shown on paid plans only.</div>
            <x-field-error name="linkedin_url" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="whatsapp_number">WhatsApp <span class="text-muted fw-normal">(optional)</span></label>
            <input id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $profile->whatsapp_number) }}" class="form-control @error('whatsapp_number') is-invalid @enderror" placeholder="+92 300 1234567">
            <div class="form-hint">Shown to patients on paid plans only.</div>
            <x-field-error name="whatsapp_number" />
        </div>
    </div>
    <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary">Save and continue <x-icon name="arrow-right" /></button></div>
</form>
@break

{{-- ───────── Step 2: focus ───────── --}}
@case('focus')
<form method="POST" action="{{ route('practitioner.onboarding.focus') }}" class="card-soft p-4">
    @csrf @method('PUT')
    @php
        $subIds = old('sub_specialties', $profile->subSpecialties->pluck('id')->all());
        $condIds = old('conditions', $profile->conditions->pluck('id')->all());
        $langIds = old('languages', $profile->languages->pluck('id')->all());
        $subs = $profile->primarySpecialty?->children()->where('is_active', true)->get() ?? collect();
    @endphp
    <h2 class="h5">Sub-specialties</h2>
    @if ($subs->isEmpty())
        <p class="text-muted small">Choose your main specialty in step 1 first.</p>
    @else
        <p class="text-muted small">Within {{ $profile->primarySpecialty->name }}, what do you focus on?</p>
        <div class="check-grid mb-4">
            @foreach ($subs as $s)
                <div class="form-check"><input class="form-check-input" type="checkbox" name="sub_specialties[]" value="{{ $s->id }}" id="sub-{{ $s->id }}" @checked(in_array($s->id, $subIds))><label class="form-check-label" for="sub-{{ $s->id }}">{{ $s->name }}</label></div>
            @endforeach
        </div>
    @endif

    <h2 class="h5">Symptoms & conditions you treat</h2>
    <p class="text-muted small">Patients often search by symptom. Pick up to 15.</p>
    <div class="check-grid mb-4">
        @foreach ($conditions as $c)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="conditions[]" value="{{ $c->id }}" id="cond-{{ $c->id }}" @checked(in_array($c->id, $condIds))><label class="form-check-label" for="cond-{{ $c->id }}">{{ $c->name }}</label></div>
        @endforeach
    </div>
    <x-field-error name="conditions" />

    <h2 class="h5">Languages you consult in</h2>
    <div class="check-grid mb-2">
        @foreach ($languages as $l)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="languages[]" value="{{ $l->id }}" id="lang-{{ $l->id }}" @checked(in_array($l->id, $langIds))><label class="form-check-label" for="lang-{{ $l->id }}">{{ $l->name }}</label></div>
        @endforeach
    </div>
    <x-field-error name="languages" />
    <div class="row g-3 mt-1 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="other_languages">Other languages</label>
            <input id="other_languages" name="other_languages" value="{{ old('other_languages', $profile->other_languages) }}" class="form-control" placeholder="e.g. Punjabi, Pashto">
        </div>
    </div>

    <h2 class="h5">Certifications & approaches <span class="text-muted fw-normal fs-6">(optional)</span></h2>
    <label class="form-label small text-muted fw-normal" for="custom_tags">Comma-separated, e.g. "EMDR, Art Therapy". New tags appear after a quick admin check.</label>
    <input id="custom_tags" name="custom_tags" value="{{ old('custom_tags', $profile->tags->pluck('name')->implode(', ')) }}" class="form-control mb-2">
    <div class="d-flex flex-wrap gap-2">
        @foreach ($profile->tags as $t)
            <span class="chip">{{ $t->name }} @if ($t->status !== \App\Enums\ModerationStatus::Approved)<small class="text-warning">· {{ strtolower($t->status->label()) }}</small>@endif</span>
        @endforeach
    </div>
    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('practitioner.onboarding.show', ['step' => 'details']) }}" class="btn btn-paper">Back</a>
        <button class="btn btn-primary">Save and continue <x-icon name="arrow-right" /></button>
    </div>
</form>
@break

{{-- ───────── Step 3: about ───────── --}}
@case('about')
<form method="POST" action="{{ route('practitioner.onboarding.about') }}" enctype="multipart/form-data" class="card-soft p-4">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3">
                <label class="form-label" for="headline">One-line introduction</label>
                <input id="headline" name="headline" maxlength="160" value="{{ old('headline', $profile->headline) }}" class="form-control @error('headline') is-invalid @enderror" placeholder="e.g. Calm, practical skin care for acne-prone and sensitive skin.">
                <div class="form-hint">Shown on your directory card. Speak to the patient, not to other doctors.</div>
                <x-field-error name="headline" />
            </div>
            <div>
                <div class="d-flex justify-content-between align-items-end">
                    <label class="form-label" for="bio">About you</label>
                    <span class="word-counter" data-word-count-for="bio"></span>
                </div>
                <textarea id="bio" name="bio" rows="10" data-word-limit="{{ \App\Models\PractitionerProfile::BIO_MAX_WORDS }}" data-word-min="40" class="form-control @error('bio') is-invalid @enderror" placeholder="Who do you usually help? What is a first consultation with you like? What should patients know before booking?">{{ old('bio', $profile->bio) }}</textarea>
                <div class="form-hint">Between 40 and 200 words. Keeps every profile easy to compare.</div>
                <x-field-error name="bio" />
            </div>
        </div>
        <div class="col-lg-4">
            <span class="form-label d-block">Professional headshot</span>
            <div class="upload-box">
                <div class="mb-3 d-flex justify-content-center">
                    @if ($profile->headshotUrl())
                        <img id="headshot-preview" src="{{ $profile->headshotUrl() }}" alt="" width="160" height="160" class="rounded-4 object-fit-cover">
                    @else
                        <div hidden><img id="headshot-preview" alt="" width="160" height="160" class="rounded-4 object-fit-cover"></div>
                        <x-avatar :profile="$profile" size="xl" />
                    @endif
                </div>
                <input type="file" name="headshot" accept="image/jpeg,image/png,image/webp" class="form-control @error('headshot') is-invalid @enderror"
                       data-max-kb="{{ \App\Models\Setting::get('headshot_max_kb', 2048) }}" data-max-message="File size exceeds 2MB. Please upload a smaller photo." data-preview="#headshot-preview">
                <div class="invalid-feedback js-file-error"></div>
                <x-field-error name="headshot" />
                <p class="form-hint mb-0 mt-2">JPG, PNG or WebP, up to 2 MB. Face clearly visible, plain background. We'll crop it square and compress it automatically.</p>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('practitioner.onboarding.show', ['step' => 'focus']) }}" class="btn btn-paper">Back</a>
        <button class="btn btn-primary">Save and continue <x-icon name="arrow-right" /></button>
    </div>
</form>

@include('practitioner.partials.intro-video')
@break

{{-- ───────── Step 4: documents ───────── --}}
@case('documents')
<form method="POST" action="{{ route('practitioner.onboarding.documents') }}" enctype="multipart/form-data" class="card-soft p-4">
    @csrf
    <div class="d-flex gap-3 mb-4 p-3 rounded-3 bg-sage">
        <x-icon name="lock" class="text-forest mt-1" />
        <div class="small"><strong>Private and secure.</strong> Documents are stored in a private folder on our server, never on the public website. Only our verification team can open them, and every view is logged.</div>
    </div>
    <x-field-error name="documents" />
    <div class="row g-3">
        @foreach ($documentTypes as $type)
            @php $doc = $profile->documents->firstWhere('type', $type); @endphp
            <div class="col-lg-4">
                <div class="upload-box text-start h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong>{{ $type->label() }}</strong>
                        @if ($doc)<x-status :status="$doc->status" />@endif
                    </div>
                    @if ($doc)
                        <a href="{{ route('documents.show', $doc) }}" target="_blank" class="small d-inline-flex gap-1 align-items-center mb-2"><x-icon name="file-text" size="sm" /> {{ \Illuminate\Support\Str::limit($doc->original_name, 28) }} ({{ $doc->sizeLabel() }})</a>
                    @else
                        <p class="small text-muted mb-2">Not uploaded yet</p>
                    @endif
                    <input type="file" name="{{ $type->value }}" accept="application/pdf,image/jpeg,image/png" class="form-control form-control-sm @error($type->value) is-invalid @enderror"
                           data-max-kb="{{ \App\Models\Setting::get('document_max_kb', 3072) }}" data-max-message="File size exceeds {{ $docMb }}MB. Please upload a compressed document to ensure fast processing">
                    <div class="invalid-feedback js-file-error"></div>
                    <x-field-error :name="$type->value" />
                    <p class="form-hint mb-0">{{ $doc ? 'Choose a file to replace it. ' : '' }}PDF, JPG or PNG, max {{ $docMb }} MB.</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('practitioner.onboarding.show', ['step' => 'about']) }}" class="btn btn-paper">Back</a>
        <button class="btn btn-primary"><x-icon name="upload" /> Upload</button>
    </div>
</form>
@break

{{-- ───────── Step 5: review ───────── --}}
@default
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Checklist</h2>
            <ul class="list-unstyled d-grid gap-2 mb-4">
                @foreach ($checklist as $item)
                    <li class="d-flex align-items-center gap-3">
                        <x-icon :name="$item['done'] ? 'check-circle' : 'circle-dot'" class="{{ $item['done'] ? 'text-forest' : 'text-muted' }}" />
                        <span class="flex-grow-1">{{ $item['label'] }}</span>
                        @unless ($item['done'])<a href="{{ route('practitioner.onboarding.show', ['step' => $item['step']]) }}" class="small">Complete</a>@endunless
                    </li>
                @endforeach
            </ul>
            @if (in_array($status, [\App\Enums\VerificationStatus::Draft, \App\Enums\VerificationStatus::Rejected], true))
                @php $ready = collect($checklist)->every(fn ($i) => $i['done']); @endphp
                <form method="POST" action="{{ route('practitioner.onboarding.submit') }}">
                    @csrf
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm" required @disabled(! $ready)>
                        <label class="form-check-label small" for="confirm">I confirm the information and documents are genuine and that I hold a valid licence to practise.</label>
                    </div>
                    <button class="btn btn-primary btn-lg" @disabled(! $ready)>Submit for verification</button>
                    @unless ($ready)<p class="small text-muted mt-2 mb-0">Complete every item above to submit.</p>@endunless
                    <x-field-error name="profile" />
                </form>
            @else
                <p class="mb-0 text-muted">Status: <x-status :status="$status" /></p>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <span class="small text-muted fw-semibold d-block mb-2">How your card will look</span>
        <x-practitioner-card :profile="$profile" />
    </div>
</div>
@endswitch
</fieldset>
@endsection
