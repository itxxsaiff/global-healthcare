@extends('layouts.dashboard')
@section('title', 'Membership')
@section('crumb', 'Membership')
@section('content')
<h1 class="dash-title">Membership</h1>
<p class="text-muted mb-4">{{ $profile->market === \App\Enums\Market::Pk ? 'Plans for practitioners based in Pakistan (PKR).' : 'Plans for practitioners outside Pakistan (USD, billed automatically).' }}</p>

@unless ($unlocked)
    <div class="alert alert-info">Plans unlock once your profile is verified. <a href="{{ route('practitioner.onboarding.show') }}">Check your profile status</a>.</div>
@endunless

@if ($current)
    <div class="card-soft p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small text-muted">Current plan</div>
            <div class="h4 mb-1">{{ $current->plan->name }} <x-status :status="$current->status" class="align-middle" /></div>
            <div class="small text-muted">
                @if ($current->ends_at)
                    {{ $current->auto_renew ? 'Renews' : 'Ends' }} {{ $current->ends_at->format('j F Y') }} ({{ $current->daysLeft() }} days)
                @else
                    No expiry · {{ $current->plan->commissionPercent() }}% commission on paid bookings
                @endif
            </div>
        </div>
        @if ($current->gateway === 'stripe' && $current->gateway_customer_id)
            <a href="{{ route('practitioner.membership.portal') }}" class="btn btn-paper"><x-icon name="credit-card" /> Manage card & billing</a>
        @endif
    </div>
@endif

<div class="row g-4">
    @foreach ($plans as $plan)
        @php $isCurrent = $current?->plan_id === $plan->id; @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card-soft p-4 h-100 d-flex flex-column {{ $isCurrent ? 'border-2 border-success' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-bold">{{ $plan->name }}</div>
                    @if ($isCurrent)<span class="status tone-success">Current</span>@endif
                </div>
                <div class="font-serif fs-2 fw-semibold my-2">{{ $plan->priceLabel() }}<span class="fs-6 text-muted fw-normal">{{ $plan->isFree() ? '' : $plan->interval->shortLabel() }}</span></div>
                <p class="small text-muted">{{ $plan->tagline }}</p>
                <ul class="list-unstyled small d-grid gap-2 mb-4">
                    <li><x-icon name="check" class="text-forest" /> {{ $plan->commission_bps ? $plan->commissionPercent().'% commission per paid booking' : '0% commission' }}</li>
                    <li><x-icon name="{{ $plan->direct_dealing ? 'check' : 'x' }}" class="{{ $plan->direct_dealing ? 'text-forest' : 'text-muted' }}" /> Direct patient dealing{{ $plan->direct_dealing ? ' & your WhatsApp' : '' }}</li>
                    <li><x-icon name="{{ $plan->shows_linkedin ? 'check' : 'x' }}" class="{{ $plan->shows_linkedin ? 'text-forest' : 'text-muted' }}" /> LinkedIn on your profile</li>
                    @if ($plan->market === \App\Enums\Market::Intl)<li><x-icon name="refresh" class="text-forest" /> Auto-renews, cancel anytime</li>@endif
                </ul>
                <form method="POST" action="{{ route('practitioner.membership.choose', $plan) }}" class="mt-auto">
                    @csrf
                    @if ($isCurrent && ! $plan->isFree() && ! $current->auto_renew)
                        <button class="btn btn-paper w-100" @disabled(! $unlocked)>Renew early</button>
                    @elseif ($isCurrent)
                        <button class="btn btn-paper w-100" disabled>Your plan</button>
                    @else
                        <button class="btn {{ $plan->isFree() ? 'btn-paper' : 'btn-primary' }} w-100" @disabled(! $unlocked)>{{ $plan->isFree() ? 'Choose free plan' : 'Choose & pay '.$plan->priceLabel() }}</button>
                    @endif
                </form>
            </div>
        </div>
    @endforeach
</div>
<x-field-error name="plan" />

@if ($profile->memberships->count() > 1)
    <h2 class="h5 mt-5 mb-3">History</h2>
    <div class="table-responsive">
        <table class="table table-clean">
            <thead><tr><th>Plan</th><th>Status</th><th>From</th><th>Until</th></tr></thead>
            <tbody>
            @foreach ($profile->memberships as $m)
                <tr><td>{{ $m->plan->name }}</td><td><x-status :status="$m->status" /></td><td class="small">{{ $m->starts_at?->format('j M Y') ?? '—' }}</td><td class="small">{{ $m->ends_at?->format('j M Y') ?? '—' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
