@extends('layouts.dashboard')
@section('title', 'Practitioners')
@section('crumb', 'Practitioners')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="dash-title">Practitioners</h1>
        <p class="text-muted mb-0">Everyone who has registered as a practitioner.</p>
    </div>
    <form class="d-flex gap-2" method="GET">
        <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Name or email">
        <select name="market" class="form-select form-select-sm"><option value="">All markets</option><option value="pk" @selected(request('market') === 'pk')>Pakistan</option><option value="intl" @selected(request('market') === 'intl')>International</option></select>
        <select name="visibility" class="form-select form-select-sm"><option value="">Any visibility</option><option value="public" @selected(request('visibility') === 'public')>Public</option><option value="hidden" @selected(request('visibility') === 'hidden')>Hidden</option></select>
        <button class="btn btn-paper btn-sm">Filter</button>
    </form>
</div>
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Practitioner</th><th>Specialty</th><th>Plan</th><th>Status</th><th>Public</th><th>Rating</th><th></th></tr></thead>
        <tbody>
        @forelse ($profiles as $p)
            <tr>
                <td><div class="fw-semibold">{{ $p->user->name }}</div><div class="small text-muted">{{ $p->user->email }} · {{ $p->country?->name }}</div></td>
                <td>{{ $p->primarySpecialty?->name ?? '—' }}</td>
                <td class="small">{{ $p->activeMembership?->plan?->name ?? '—' }}</td>
                <td><x-status :status="$p->verification_status" /></td>
                <td>{!! $p->is_public ? '<span class="status tone-success">Live</span>' : '<span class="status tone-neutral">Hidden</span>' !!}</td>
                <td class="small">{{ $p->reviews_count ? number_format($p->rating_avg, 1).' ('.$p->reviews_count.')' : '—' }}</td>
                <td class="text-end"><a href="{{ route('admin.verifications.show', $p) }}" class="btn btn-sm btn-paper">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No practitioners match.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $profiles->links() }}
@endsection
