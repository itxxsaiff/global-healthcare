@extends('layouts.dashboard')
@section('title', 'Audit log')
@section('crumb', 'Audit log')
@section('content')
<h1 class="dash-title">Audit log</h1>
<p class="text-muted mb-3">Sensitive actions: document views, verification decisions, refunds, payouts, suspensions and settings changes.</p>
<form class="d-flex gap-2 mb-3" style="max-width: 420px"><input name="action" value="{{ request('action') }}" class="form-control form-control-sm" placeholder="Filter, e.g. document."><button class="btn btn-sm btn-paper">Filter</button></form>
<div class="table-responsive">
    <table class="table table-clean small">
        <thead><tr><th>When (UTC)</th><th>Who</th><th>Action</th><th>Subject</th><th>IP</th><th>Details</th></tr></thead>
        <tbody>
        @foreach ($logs as $l)
            <tr>
                <td class="text-nowrap">{{ $l->created_at->format('j M Y, H:i:s') }}</td>
                <td>{{ $l->actor?->name ?? 'System' }}</td>
                <td class="font-monospace">{{ $l->action }}</td>
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
