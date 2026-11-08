@extends('layouts.dashboard')
@section('title', 'Verification queue')
@section('crumb', 'Verification queue')
@section('content')
<h1 class="dash-title">Verification queue</h1>
<p class="text-muted mb-4">Check each practitioner's ID, degree and licence before their profile can go live.</p>

<ul class="nav nav-pills gap-2 mb-3">
    @foreach (['pending' => 'Waiting for review', 'rejected' => 'Changes requested', 'draft' => 'Still completing profile'] as $key => $label)
        <li class="nav-item"><a class="chip {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.verifications.index', ['status' => $key]) }}">{{ $label }} <strong>{{ $counts[$key] ?? 0 }}</strong></a></li>
    @endforeach
</ul>

@if ($profiles->isEmpty())
    <x-empty icon="check-circle" title="Nothing here" text="No practitioners in this list right now." />
@else
    <div class="table-card table-responsive">
        <table class="table table-clean">
            <thead><tr><th>Practitioner</th><th>Specialty</th><th>Country</th><th>Documents</th><th>{{ $tab === 'pending' ? 'Submitted' : 'Updated' }}</th><th class="col-actions">Actions</th></tr></thead>
            <tbody>
            @foreach ($profiles as $p)
                <tr>
                    <td>
                        <div class="cell-user">
                            <x-avatar :profile="$p" size="sm" round />
                            <div class="min-w-0"><div class="name">{{ $p->user->name }}</div><div class="sub">{{ $p->user->email }}</div></div>
                        </div>
                    </td>
                    <td>{{ $p->primarySpecialty?->name ?? '—' }}</td>
                    <td>{{ $p->country?->name }}</td>
                    @php $docs = $p->documents->pluck('type')->unique()->count(); @endphp
                    <td><span class="status tone-{{ $docs === 3 ? 'success' : 'warning' }}">{{ $docs }} / 3 files</span></td>
                    <td class="small text-nowrap">{{ ($tab === 'pending' ? $p->submitted_at : $p->updated_at)?->diffForHumans() }}</td>
                    <td class="col-actions"><div class="actions">@if ($tab === 'pending')<x-act icon="shield-check-line" label="Review documents" tone="success" :href="route('admin.verifications.show', $p)" />@else<x-act icon="eye-line" label="View profile" :href="route('admin.verifications.show', $p)" />@endif</div></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $profiles->links() }}
@endif
@endsection
