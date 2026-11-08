@extends('layouts.dashboard')
@section('title', 'Practitioners')
@section('crumb', 'Practitioners')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Practitioners</h1>
        <p class="text-muted mb-0">Everyone who has registered as a practitioner.</p>
    </div>
    <form class="table-toolbar mb-0" method="GET">
        <div class="search"><i class="ri-search-line"></i><input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Name or email"></div>
        <select name="market" class="form-select form-select-sm"><option value="">All markets</option><option value="pk" @selected(request('market') === 'pk')>Pakistan</option><option value="intl" @selected(request('market') === 'intl')>International</option></select>
        <select name="visibility" class="form-select form-select-sm"><option value="">Any visibility</option><option value="public" @selected(request('visibility') === 'public')>Public</option><option value="hidden" @selected(request('visibility') === 'hidden')>Hidden</option></select>
        <button class="btn btn-primary btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
    </form>
</div>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Practitioner</th><th>Specialty</th><th>Plan</th><th>Status</th><th>Public</th><th>Rating</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @forelse ($profiles as $p)
            <tr>
                <td><div class="cell-user"><x-avatar :profile="$p" size="sm" round /><div class="min-w-0"><div class="name">{{ $p->user->name }}</div><div class="sub">{{ $p->user->email }} · {{ $p->country?->name }}</div></div></div></td>
                <td>{{ $p->primarySpecialty?->name ?? '—' }}</td>
                <td class="small">{{ $p->activeMembership?->plan?->name ?? '—' }}</td>
                <td><x-status :status="$p->verification_status" /></td>
                <td>{!! $p->is_public ? '<span class="status tone-success">Live</span>' : '<span class="status tone-neutral">Hidden</span>' !!}</td>
                <td class="small text-nowrap">@if ($p->reviews_count)<i class="ri-star-fill" style="color:#F2B23A"></i> {{ number_format($p->rating_avg, 1) }} <span class="text-muted">({{ $p->reviews_count }})</span>@else — @endif</td>
                <td class="col-actions"><div class="actions">
                    <x-act icon="file-user-line" label="Profile & documents" :href="route('admin.verifications.show', $p)" />
                    @if ($p->is_public)<x-act icon="external-link-line" label="View public profile" tone="neutral" :href="route('practitioners.show', $p)" target="_blank" />@endif
                    <form method="POST" action="{{ route('admin.users.toggle', $p->user) }}" onsubmit="return confirm('{{ $p->user->isActive() ? 'Suspend' : 'Reactivate' }} {{ $p->user->name }}?')">@csrf
                        @if ($p->user->isActive())<x-act icon="forbid-line" label="Suspend account" tone="danger" type="submit" />@else<x-act icon="checkbox-circle-line" label="Reactivate account" tone="success" type="submit" />@endif
                    </form>
                </div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="7"><i class="ri-user-search-line"></i>No practitioners match.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $profiles->links() }}
@endsection
