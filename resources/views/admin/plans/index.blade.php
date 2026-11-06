@extends('layouts.dashboard')
@section('title', 'Membership plans')
@section('crumb', 'Membership plans')
@section('content')
<h1 class="dash-title">Membership plans</h1>
<p class="text-muted mb-4">Changes apply to new purchases and new bookings only. Existing bookings keep the fee and commission they were made with.</p>
<div class="row g-3">
    @foreach ($plans as $plan)
        <div class="col-md-6 col-xl-4">
            <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="card-soft p-4 h-100">
                @csrf @method('PUT')
                <div class="d-flex justify-content-between mb-2">
                    <span class="small text-muted font-monospace">{{ $plan->code }} · {{ $plan->market->label() }} · {{ $plan->interval->label() }}</span>
                    <span class="small">{{ $plan->active_count }} active</span>
                </div>
                <input name="name" value="{{ $plan->name }}" class="form-control fw-semibold mb-2">
                <input name="tagline" value="{{ $plan->tagline }}" class="form-control form-control-sm mb-2" placeholder="Tagline">
                <div class="row g-2 mb-2">
                    <div class="col-7"><label class="small text-muted">Price ({{ $plan->currency }})</label><input type="number" step="0.01" min="0" name="price" value="{{ $plan->price_minor / 100 }}" class="form-control"></div>
                    <div class="col-5"><label class="small text-muted">Commission %</label><input type="number" step="0.5" min="0" max="50" name="commission_percent" value="{{ $plan->commission_bps / 100 }}" class="form-control"></div>
                </div>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $plan->id }}" @checked($plan->is_active)><label class="form-check-label small" for="active-{{ $plan->id }}">Available to buy</label></div>
                <button class="btn btn-sm btn-primary">Save</button>
            </form>
        </div>
    @endforeach
</div>
@endsection
