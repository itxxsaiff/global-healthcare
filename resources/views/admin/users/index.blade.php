@extends('layouts.dashboard')
@section('title', 'Users')
@section('crumb', 'Users')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <h1 class="dash-title mb-0">Users</h1>
    <form class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Name or email">
        <select name="role" class="form-select form-select-sm"><option value="">All roles</option>@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ ucfirst($r->value) }}</option>@endforeach</select>
        <button class="btn btn-sm btn-paper">Filter</button>
    </form>
</div>
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Last login</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td><div class="fw-semibold">{{ $u->name }}</div><div class="small text-muted">{{ $u->email }}@unless ($u->email_verified_at) · <span class="text-warning">unverified</span>@endunless</div></td>
                <td class="text-capitalize">{{ $u->role->value }}</td>
                <td class="small">{{ $u->created_at->format('j M Y') }}</td>
                <td class="small">{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
                <td>{!! $u->isActive() ? '<span class="status tone-success">Active</span>' : '<span class="status tone-danger">Suspended</span>' !!}</td>
                <td class="text-end">
                    @unless ($u->id === auth()->id())
                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}" onsubmit="return confirm('{{ $u->isActive() ? 'Suspend' : 'Reactivate' }} {{ $u->name }}?')">@csrf<button class="btn btn-sm {{ $u->isActive() ? 'btn-outline-danger' : 'btn-paper' }}">{{ $u->isActive() ? 'Suspend' : 'Reactivate' }}</button></form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
