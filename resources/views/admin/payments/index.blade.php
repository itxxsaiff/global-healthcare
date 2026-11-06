@extends('layouts.dashboard')
@section('title', 'Payments')
@section('crumb', 'Payments')
@section('content')
<h1 class="dash-title">Payments</h1>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.payments.index') }}" class="chip {{ ! $status ? 'active' : '' }}">All</a>
    @foreach (\App\Enums\PaymentStatus::cases() as $s)<a href="{{ route('admin.payments.index', ['status' => $s->value]) }}" class="chip {{ $status === $s ? 'active' : '' }}">{{ ucfirst($s->value) }}</a>@endforeach
</div>
@if ($failedEvents->isNotEmpty())
    <div class="alert alert-warning small"><strong>Gateway events needing attention:</strong>
        @foreach ($failedEvents as $e)<div>{{ $e->created_at->format('j M H:i') }} · {{ $e->gateway }} · {{ $e->type }} · {{ $e->error }}</div>@endforeach
    </div>
@endif
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Date</th><th>Payer</th><th>For</th><th>Gateway</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($payments as $p)
            <tr>
                <td class="small text-nowrap">{{ $p->created_at->format('j M Y, H:i') }}</td>
                <td>{{ $p->user->name }}</td>
                <td class="small">{{ $p->payable instanceof \App\Models\Booking ? 'Booking '.$p->payable->reference : 'Membership' }}</td>
                <td class="small font-monospace">{{ $p->gateway }}</td>
                <td>{{ $p->amountLabel() }}</td>
                <td><span class="status tone-{{ $p->status->tone() }}">{{ ucfirst($p->status->value) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No payments yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $payments->links() }}
@endsection
