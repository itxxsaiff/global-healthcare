@extends('layouts.app')
@section('title', 'List your practice')
@section('description', 'Join a verified directory of doctors and therapists. Free listing with 20% commission, or a flat subscription with 0% commission. PKR and USD plans.')
@section('content')
<section class="hero pb-4">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="eyebrow mb-3">For doctors, psychologists & therapists</span>
                <h1 class="mb-4">Your expertise, <x-scribble>without</x-scribble> the admin.</h1>
                <p class="hero-lead mb-4">We handle discovery, scheduling across time zones, payment reminders and reviews. You focus on patients. Get verified once, then set your own hours and fees.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('register.practitioner') }}" class="btn btn-primary btn-lg">Apply to join</a>
                    <a href="#plans" class="btn btn-paper btn-lg">See plans</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-soft p-4">
                    <h2 class="h5 mb-3">What you get</h2>
                    <ul class="list-unstyled d-grid gap-3 mb-0">
                        <li class="d-flex gap-3"><x-icon name="search" class="text-forest mt-1" /><span><strong>Found by symptom, not just title.</strong> Patients who type “PCOS” or “panic attacks” find you.</span></li>
                        <li class="d-flex gap-3"><x-icon name="clock" class="text-forest mt-1" /><span><strong>Time-zone smart calendar.</strong> Set hours once; patients in Dubai or Toronto see their local time.</span></li>
                        <li class="d-flex gap-3"><x-icon name="star" class="text-forest mt-1" /><span><strong>Verified reviews only.</strong> Only patients with a completed consultation can review you.</span></li>
                        <li class="d-flex gap-3"><x-icon name="book-open" class="text-forest mt-1" /><span><strong>Build authority.</strong> Answer public questions and publish articles that link back to your profile.</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="plans">
    <div class="container">
        <div class="text-center mb-5">
            <span class="eyebrow mb-2">Plans & pricing</span>
            <h2 class="section-title">Simple pricing. Pick what suits your practice.</h2>
            <p class="section-lead mx-auto">Your plan options depend on where you practise. Pakistan-based practitioners pay in PKR; everyone else in USD.</p>
        </div>

        <h3 class="h5 mb-3">Practising in Pakistan</h3>
        <div class="row g-4 mb-5">
            @foreach ($plans['pk'] ?? [] as $plan)
                <div class="col-md-6 col-xl-3">
                    <div class="card-soft p-4 h-100 d-flex flex-column {{ $plan->code === 'pk_pro_1m' ? 'border-2 border-success' : '' }}">
                        <div class="fw-bold">{{ $plan->name }}</div>
                        <div class="font-serif fs-2 fw-semibold my-2">{{ $plan->priceLabel() }}<span class="fs-6 text-muted fw-normal">{{ $plan->isFree() ? '' : $plan->interval->shortLabel() }}</span></div>
                        <p class="small text-muted">{{ $plan->tagline }}</p>
                        <ul class="list-unstyled small d-grid gap-2 mt-auto mb-0">
                            <li><x-icon name="check" class="text-forest" /> {{ $plan->commission_bps ? $plan->commissionPercent().'% commission on paid bookings' : '0% commission' }}</li>
                            <li><x-icon name="{{ $plan->direct_dealing ? 'check' : 'x' }}" class="{{ $plan->direct_dealing ? 'text-forest' : 'text-muted' }}" /> Direct patient dealing</li>
                            <li><x-icon name="{{ $plan->shows_linkedin ? 'check' : 'x' }}" class="{{ $plan->shows_linkedin ? 'text-forest' : 'text-muted' }}" /> LinkedIn link on profile</li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        <h3 class="h5 mb-3">Practising outside Pakistan</h3>
        <div class="row g-4">
            @foreach ($plans['intl'] ?? [] as $plan)
                <div class="col-md-4">
                    <div class="card-soft p-4 h-100 {{ $plan->code === 'intl_12m' ? 'border-2 border-success' : '' }}">
                        <div class="fw-bold">{{ $plan->name }}</div>
                        <div class="font-serif fs-2 fw-semibold my-2">{{ $plan->priceLabel() }}<span class="fs-6 text-muted fw-normal">{{ $plan->interval->shortLabel() }}</span></div>
                        <p class="small text-muted mb-3">{{ $plan->tagline }}</p>
                        <ul class="list-unstyled small d-grid gap-2 mb-0">
                            <li><x-icon name="check" class="text-forest" /> 0% commission, direct client dealing</li>
                            <li><x-icon name="check" class="text-forest" /> LinkedIn link on profile</li>
                            <li><x-icon name="check" class="text-forest" /> Renews automatically, cancel anytime</li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-4"><x-icon name="info" size="sm" /> Your profile goes live once our team has verified your documents and you've chosen a plan. Profiles on paid plans are hidden automatically if the subscription lapses.</p>
    </div>
</section>
@endsection
