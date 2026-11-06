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
    <div class="table-responsive">
        <table class="table table-clean">
            <thead><tr><th>Practitioner</th><th>Specialty</th><th>Country</th><th>Documents</th><th>{{ $tab === 'pending' ? 'Submitted' : 'Updated' }}</th><th></th></tr></thead>
            <tbody>
            @foreach ($profiles as $p)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <x-avatar :profile="$p" size="sm" />
                            <div><div class="fw-semibold">{{ $p->user->name }}</div><div class="small text-muted">{{ $p->user->email }}</div></div>
                        </div>
                    </td>
                    <td>{{ $p->primarySpecialty?->name ?? '—' }}</td>
                    <td>{{ $p->country?->name }}</td>
                    <td><span class="small {{ $p->documents->pluck('type')->unique()->count() === 3 ? 'text-success' : 'text-muted' }}">{{ $p->documents->pluck('type')->unique()->count() }} / 3</span></td>
                    <td class="small text-nowrap">{{ ($tab === 'pending' ? $p->submitted_at : $p->updated_at)?->diffForHumans() }}</td>
                    <td class="text-end"><a href="{{ route('admin.verifications.show', $p) }}" class="btn btn-sm {{ $tab === 'pending' ? 'btn-primary' : 'btn-paper' }}">{{ $tab === 'pending' ? 'Review' : 'Open' }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $profiles->links() }}
@endif
@endsection
