@extends('layouts.dashboard')
@section('title', 'Admin dashboard')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->firstName() }}</h1>
        <p class="text-muted mb-0">Here's what needs your attention today.</p>
    </div>
    @if (Route::has('admin.verifications.index') && $stats['pending'])
        <a href="{{ route('admin.verifications.index') }}" class="btn btn-clay"><x-icon name="shield-check" /> Review {{ $stats['pending'] }} {{ \Illuminate\Support\Str::plural('application', $stats['pending']) }}</a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Live practitioners</div><div class="stat-value">{{ $stats['public'] }}</div><div class="stat-note">{{ $stats['pending'] }} awaiting verification</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Patients</div><div class="stat-value">{{ number_format($stats['patients']) }}</div><div class="stat-note">registered accounts</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Bookings this month</div><div class="stat-value">{{ $stats['bookingsMonth'] }}</div><div class="stat-note">{{ $stats['awaitingResponse'] }} awaiting practitioner reply</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat"><div class="stat-label">Commission this month</div>
        <div class="stat-value">{{ \App\Support\Money::format((int) ($commission['PKR'] ?? 0), 'PKR') }}</div>
        <div class="stat-note">from Plan 1 bookings{{ isset($commission['USD']) ? ' · '.\App\Support\Money::format((int) $commission['USD'], 'USD') : '' }}</div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Verification queue</h2>
            @forelse ($queue as $p)
                <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                    <x-avatar :profile="$p" size="sm" />
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">{{ $p->user->name }}</div>
                        <div class="small text-muted">{{ $p->primarySpecialty?->name ?? 'No specialty yet' }} · {{ $p->country?->name }}</div>
                    </div>
                    <small class="text-muted text-nowrap">{{ $p->submitted_at?->diffForHumans() }}</small>
                </div>
            @empty
                <x-empty icon="check-circle" title="All caught up" text="No practitioners are waiting for verification." class="border-0 bg-transparent py-4" />
            @endforelse
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card-soft p-4 h-100">
            <h2 class="h5 mb-3">Latest bookings</h2>
            @if ($recentBookings->isEmpty())
                <x-empty icon="calendar" title="No bookings yet" text="Bookings will appear here as soon as patients start requesting appointments." class="border-0 bg-transparent py-4" />
            @else
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead><tr><th>Ref</th><th>Patient</th><th>Practitioner</th><th>When (UTC)</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentBookings as $b)
                            <tr>
                                <td class="font-monospace small">{{ $b->reference }}</td>
                                <td>{{ $b->patient->name }}</td>
                                <td>{{ $b->practitioner->user->name }}</td>
                                <td class="small text-nowrap">{{ $b->starts_at->format('j M, H:i') }}</td>
                                <td><x-status :status="$b->status" /></td>
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
