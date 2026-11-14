@extends('layouts.dashboard')
@section('title', 'Admin dashboard')
@section('content')
<x-dash-hero :title="auth()->user()->name">
    Here's what needs your attention on {{ config('app.name') }} today.
    @if (Route::has('admin.verifications.index') && $stats['pending'])
        <x-slot:actions>
            <a href="{{ route('admin.verifications.index') }}" class="btn btn-primary"><x-icon name="shield-check" /> Review {{ $stats['pending'] }} {{ \Illuminate\Support\Str::plural('application', $stats['pending']) }}</a>
        </x-slot:actions>
    @endif
</x-dash-hero>

<div class="row g-3 mb-4">
    <div class="col-6 col-xxl-3"><x-stat icon="stethoscope" tone="teal" label="Live practitioners" :value="$stats['public']" :note="$stats['pending'].' awaiting verification'" /></div>
    <div class="col-6 col-xxl-3"><x-stat icon="users" tone="sky" label="Patients" :value="number_format($stats['patients'])" note="registered accounts" /></div>
    <div class="col-6 col-xxl-3"><x-stat icon="calendar" tone="amber" label="Bookings this month" :value="$stats['bookingsMonth']" :note="$stats['awaitingResponse'].' awaiting practitioner reply'" /></div>
    <div class="col-6 col-xxl-3"><x-stat icon="wallet" tone="coral" label="Commission this month" :value="\App\Support\Money::format((int) ($commission['PKR'] ?? 0), 'PKR')" :note="'Starter plan, after refunds'.(isset($commission['USD']) ? ' · '.\App\Support\Money::format((int) $commission['USD'], 'USD') : '')" /></div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card-soft p-4 h-100">
            <div class="card-title-row"><h2>Verification queue</h2>@if (Route::has('admin.verifications.index'))<a href="{{ route('admin.verifications.index') }}">View all</a>@endif</div>
            @forelse ($queue as $p)
                <div class="list-row">
                    <x-avatar :profile="$p" size="sm" />
                    <div class="flex-grow-1 min-w-0">
                        <div class="lr-title text-truncate">{{ $p->user->name }}</div>
                        <div class="lr-sub">{{ $p->primarySpecialty?->name ?? 'No specialty yet' }} · {{ $p->country?->name }}</div>
                    </div>
                    <small class="text-muted text-nowrap">{{ $p->submitted_at?->diffForHumans() }}</small>
                </div>
            @empty
                <x-empty icon="check-circle" title="All caught up" text="No practitioners are waiting for verification." class="border-0 bg-transparent py-4" />
            @endforelse
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card-soft p-4 h-100">
            <div class="card-title-row"><h2>Latest bookings</h2>@if (Route::has('admin.bookings.index'))<a href="{{ route('admin.bookings.index') }}">View all</a>@endif</div>
            @if ($recentBookings->isEmpty())
                <x-empty icon="calendar" title="No bookings yet" text="Bookings will appear here as soon as patients start requesting appointments." class="border-0 bg-transparent py-4" />
            @else
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead><tr><th>Ref</th><th>Patient</th><th>Practitioner</th><th>When (UTC)</th><th>Status</th><th class="col-actions"></th></tr></thead>
                        <tbody>
                        @foreach ($recentBookings as $b)
                            <tr>
                                <td><span class="ref">{{ $b->reference }}</span></td>
                                <td class="text-nowrap"><div class="cell-user"><x-avatar :name="$b->patient->name" size="xs" round /><span class="name">{{ $b->patient->name }}</span></div></td>
                                <td class="text-nowrap"><div class="cell-user"><x-avatar :profile="$b->practitioner" size="xs" round /><span class="name">{{ $b->practitioner->user->name }}</span></div></td>
                                <td class="small text-nowrap">{{ $b->starts_at->format('j M, H:i') }}</td>
                                <td><x-status :status="$b->status" /></td>
                                <td class="col-actions">@if (Route::has('admin.bookings.show'))<x-act icon="eye-line" label="View booking" :href="route('admin.bookings.show', $b)" />@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
