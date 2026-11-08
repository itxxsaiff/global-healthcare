@extends('layouts.dashboard')
@section('title', 'Users')
@section('crumb', 'Users')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <h1 class="dash-title mb-0">Users</h1>
    <form class="table-toolbar mb-0">
        <div class="search"><i class="ri-search-line"></i><input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Name or email"></div>
        <select name="role" class="form-select form-select-sm"><option value="">All roles</option>@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ ucfirst($r->value) }}</option>@endforeach</select>
        <button class="btn btn-sm btn-primary"><i class="ri-filter-3-line"></i> Filter</button>
    </form>
</div>
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Last login</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td><div class="cell-user"><x-avatar :name="$u->name" :src="$u->practitionerProfile?->headshotUrl()" size="sm" round /><div class="min-w-0"><div class="name">{{ $u->name }}</div><div class="sub">{{ $u->email }}@unless ($u->email_verified_at) · <span class="text-warning">unverified</span>@endunless</div></div></div></td>
                <td><span class="status tone-{{ ['admin' => 'info', 'practitioner' => 'success', 'patient' => 'neutral'][$u->role->value] ?? 'neutral' }} text-capitalize">{{ $u->role->value }}</span></td>
                <td class="small">{{ $u->created_at->format('j M Y') }}</td>
                <td class="small">{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
                <td>{!! $u->isActive() ? '<span class="status tone-success">Active</span>' : '<span class="status tone-danger">Suspended</span>' !!}</td>
                <td class="col-actions"><div class="actions">
                    @if ($u->practitionerProfile)<x-act icon="file-user-line" label="Practitioner profile" :href="route('admin.verifications.show', $u->practitionerProfile)" />@endif
                    @unless ($u->id === auth()->id())
                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}" onsubmit="return confirm('{{ $u->isActive() ? 'Suspend' : 'Reactivate' }} {{ $u->name }}?')">@csrf
                            @if ($u->isActive())<x-act icon="forbid-line" label="Suspend account" tone="danger" type="submit" />@else<x-act icon="checkbox-circle-line" label="Reactivate account" tone="success" type="submit" />@endif
                        </form>
                    @endunless
                </div></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
