@extends('layouts.dashboard')
@section('title', 'Notifications')
@section('crumb', 'Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <h1 class="dash-title mb-0">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-paper btn-sm"><i class="ri-check-double-line"></i> Mark all as read</button></form>
</div>
@forelse ($notifications as $n)
    <a href="{{ route('notifications.open', $n->id) }}" class="card-soft card-hover p-3 mb-2 d-flex gap-3 align-items-center text-reset text-decoration-none {{ $n->read_at ? 'opacity-75' : '' }}">
        <span class="d-grid rounded-3 flex-shrink-0" style="width:40px;height:40px;place-items:center;background:#E3F4EF;color:#0E8474"><x-icon :name="$n->data['icon'] ?? 'bell'" /></span>
        <div class="flex-grow-1">
            <div class="{{ $n->read_at ? '' : 'fw-semibold' }}">{{ $n->data['title'] ?? 'Update' }}</div>
            <div class="small text-muted">{{ $n->created_at->diffForHumans() }}</div>
        </div>
        @unless ($n->read_at)<span class="badge rounded-pill text-bg-warning">New</span>@endunless
    </a>
@empty
    <x-empty icon="bell" title="You're all caught up" />
@endforelse
{{ $notifications->links() }}
@endsection
