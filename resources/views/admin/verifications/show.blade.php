@extends('layouts.dashboard')
@section('title', 'Review '.$profile->user->name)
@section('crumb', 'Verification')
@php $status = $profile->verification_status; @endphp
@section('content')
<a href="{{ route('admin.verifications.index') }}" class="small text-muted d-inline-flex gap-1 align-items-center mb-3"><x-icon name="arrow-left" size="sm" /> Back to queue</a>
<div class="d-flex flex-wrap gap-3 align-items-center mb-4">
    <x-avatar :profile="$profile" size="lg" />
    <div class="flex-grow-1">
        <h1 class="dash-title mb-1">{{ $profile->displayName() }}</h1>
        <div class="text-muted">{{ $profile->primarySpecialty?->name }} · {{ $profile->locationLabel() }} · {{ $profile->market->label() }} market</div>
        <div class="small text-muted">{{ $profile->user->email }} · joined {{ $profile->user->created_at->format('j M Y') }}</div>
    </div>
    <x-status :status="$status" />
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-3">Documents</h2>
            <div class="row g-3">
                @foreach (\App\Enums\DocumentType::cases() as $type)
                    @php $doc = $profile->documents->firstWhere('type', $type); @endphp
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100 bg-white">
                            <div class="small fw-bold mb-2">{{ $type->label() }}</div>
                            @if ($doc)
                                @if ($doc->isImage())
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank"><img src="{{ route('documents.show', $doc) }}" alt="{{ $type->label() }}" class="w-100 rounded-2 border mb-2" style="max-height:180px;object-fit:cover"></a>
                                @else
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" class="d-grid bg-paper rounded-2 border mb-2 text-decoration-none" style="height:120px;place-items:center"><span class="text-center"><x-icon name="file-text" size="lg" /><br><small>Open PDF</small></span></a>
                                @endif
                                <div class="small text-muted text-truncate">{{ $doc->original_name }}</div>
                                <div class="small text-muted">{{ $doc->sizeLabel() }} · {{ $doc->created_at->format('j M Y') }}</div>
                            @else
                                <p class="small text-danger mb-0">Missing</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-muted mt-3 mb-0"><x-icon name="eye" size="sm" /> Each time you open a document it is recorded in the audit log.</p>
        </div>

        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-3">Professional details</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4 text-muted">Licence number</dt><dd class="col-sm-8 font-monospace">{{ $profile->license_number ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">Licensing body</dt><dd class="col-sm-8">{{ $profile->licensing_body ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">Qualifications</dt><dd class="col-sm-8">{{ $profile->qualifications ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">Experience</dt><dd class="col-sm-8">{{ $profile->years_experience }} years</dd>
                <dt class="col-sm-4 text-muted">Gender</dt><dd class="col-sm-8">{{ $profile->gender?->label() }}</dd>
                <dt class="col-sm-4 text-muted">Fee</dt><dd class="col-sm-8">{{ $profile->feeLabel() }} / {{ $profile->session_minutes }} min</dd>
                <dt class="col-sm-4 text-muted">Sub-specialties</dt><dd class="col-sm-8">{{ $profile->subSpecialties->pluck('name')->implode(', ') ?: '—' }}</dd>
                <dt class="col-sm-4 text-muted">Symptoms</dt><dd class="col-sm-8">{{ $profile->conditions->pluck('name')->implode(', ') ?: '—' }}</dd>
                <dt class="col-sm-4 text-muted">Languages</dt><dd class="col-sm-8">{{ $profile->languages->pluck('name')->merge(array_filter([$profile->other_languages]))->implode(', ') }}</dd>
                <dt class="col-sm-4 text-muted">Custom tags</dt><dd class="col-sm-8">{{ $profile->tags->map(fn ($t) => $t->name.' ('.$t->status->value.')')->implode(', ') ?: '—' }}</dd>
                <dt class="col-sm-4 text-muted">Links</dt><dd class="col-sm-8">
                    @if ($profile->linkedin_url)<a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener nofollow">LinkedIn</a>@endif
                    @if ($profile->intro_video_url) · <a href="{{ $profile->intro_video_url }}" target="_blank" rel="noopener nofollow">Video</a>@endif
                </dd>
            </dl>
        </div>

        <div class="card-soft p-4">
            <h2 class="h5 mb-2">Bio</h2>
            <p class="fw-semibold">{{ $profile->headline }}</p>
            <p class="mb-0" style="white-space: pre-line">{{ $profile->bio }}</p>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-3">Decision</h2>
            @if ($status === \App\Enums\VerificationStatus::Pending)
                <form method="POST" action="{{ route('admin.verifications.approve', $profile) }}" class="mb-3" onsubmit="return confirm('Approve this practitioner?')">
                    @csrf
                    <button class="btn btn-primary w-100"><x-icon name="badge-check" /> Approve & unlock plans</button>
                </form>
                <form method="POST" action="{{ route('admin.verifications.reject', $profile) }}">
                    @csrf
                    <label class="form-label small" for="reason">Or request changes</label>
                    <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror mb-2" placeholder="e.g. The licence certificate is blurry. Please upload a clearer scan.">{{ old('reason') }}</textarea>
                    <x-field-error name="reason" />
                    <button class="btn btn-paper w-100"><i class="ri-arrow-go-back-line"></i> Send back with this note</button>
                </form>
            @elseif ($status === \App\Enums\VerificationStatus::Approved)
                <p class="small text-muted">Verified {{ $profile->verified_at?->format('j M Y') }}.</p>
                <form method="POST" action="{{ route('admin.verifications.suspend', $profile) }}">
                    @csrf
                    <textarea name="reason" rows="2" class="form-control mb-2" placeholder="Reason for suspension (internal)" required></textarea>
                    <button class="btn btn-outline-danger w-100"><i class="ri-forbid-line"></i> Suspend profile</button>
                </form>
            @elseif ($status === \App\Enums\VerificationStatus::Suspended)
                <p class="small">Suspended: {{ $profile->rejection_reason }}</p>
                <form method="POST" action="{{ route('admin.verifications.reinstate', $profile) }}">@csrf<button class="btn btn-primary w-100"><i class="ri-checkbox-circle-line"></i> Reinstate</button></form>
            @else
                <p class="small text-muted mb-0">No action available while the profile is "{{ $status->label() }}".</p>
            @endif
            <x-field-error name="profile" />
        </div>

        <div class="card-soft p-4 mb-4">
            <h2 class="h6 mb-2">Checklist</h2>
            @foreach ($checklist as $item)
                <div class="small d-flex gap-2 align-items-center mb-1"><x-icon :name="$item['done'] ? 'check-circle' : 'x-circle'" class="{{ $item['done'] ? 'text-success' : 'text-danger' }}" /> {{ $item['label'] }}</div>
            @endforeach
            <a href="{{ route('practitioners.show', $profile) }}" target="_blank" class="btn btn-paper btn-sm mt-3 w-100"><x-icon name="eye" /> Preview public profile</a>
        </div>

        <div class="card-soft p-4">
            <h2 class="h6 mb-2">History</h2>
            <ul class="timeline mb-0">
                @forelse ($history as $h)
                    <li><strong>{{ str_replace(['practitioner.', '_'], ['', ' '], $h->action) }}</strong> · {{ $h->actor?->name ?? 'System' }}<br><small class="text-muted">{{ $h->created_at->format('j M Y, H:i') }}</small></li>
                @empty
                    <li class="text-muted">No history yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
