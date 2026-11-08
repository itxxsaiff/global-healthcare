@extends('layouts.dashboard')
@section('title', 'Audit log')
@section('crumb', 'Audit log')
@section('content')
<h1 class="dash-title">Audit log</h1>
<p class="text-muted mb-3">Sensitive actions: document views, verification decisions, refunds, payouts, suspensions and settings changes.</p>
<form class="table-toolbar justify-content-start"><div class="search"><i class="ri-search-line"></i><input name="action" value="{{ request('action') }}" class="form-control form-control-sm" placeholder="Filter, e.g. document."></div><button class="btn btn-sm btn-primary"><i class="ri-filter-3-line"></i> Filter</button></form>
<div class="table-card table-responsive">
    <table class="table table-clean small">
        <thead><tr><th>When (UTC)</th><th>Who</th><th>Action</th><th>Subject</th><th>IP</th><th>Details</th></tr></thead>
        <tbody>
        @foreach ($logs as $l)
            <tr>
                <td class="text-nowrap">{{ $l->created_at->format('j M Y, H:i:s') }}</td>
                <td>{{ $l->actor?->name ?? 'System' }}</td>
                <td><span class="ref">{{ $l->action }}</span></td>
                <td>{{ $l->subject_type ? $l->subject_type.' #'.$l->subject_id : '—' }}</td>
                <td class="font-monospace">{{ $l->ip }}</td>
                <td class="text-muted">{{ $l->meta ? \Illuminate\Support\Str::limit(json_encode($l->meta), 90) : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $logs->links() }}
@endsection
