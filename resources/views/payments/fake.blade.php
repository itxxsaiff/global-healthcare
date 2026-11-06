@extends('layouts.app')
@section('title', 'Test payment')
@section('noindex', true)
@section('hide_whatsapp', true)
@section('content')
<div class="container py-5" style="max-width: 520px">
    <div class="card-soft p-4 text-center">
        <span class="status tone-warning mb-3">Test mode: no real money</span>
        <h1 class="h3">Test payment</h1>
        <p class="text-muted">This page stands in for JazzCash / PayFast / Stripe until the client's merchant accounts are connected.</p>
        <div class="font-serif fs-1 fw-semibold my-3">{{ $payment->amountLabel() }}</div>
        <p class="small text-muted">{{ $payment->payable instanceof \App\Models\Booking ? 'Consultation '.$payment->payable->reference : 'Membership plan' }}</p>
        @php $action = URL::temporarySignedRoute('payments.fake.complete', now()->addHour(), $payment); @endphp
        <div class="d-grid gap-2 mt-4">
            <form method="POST" action="{{ $action }}">@csrf<input type="hidden" name="outcome" value="pay"><button class="btn btn-primary btn-lg w-100">Simulate successful payment</button></form>
            <form method="POST" action="{{ $action }}">@csrf<input type="hidden" name="outcome" value="fail"><button class="btn btn-paper w-100">Simulate failed payment</button></form>
        </div>
    </div>
</div>
@endsection
