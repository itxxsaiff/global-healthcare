@extends('layouts.dashboard')
@section('title', 'Custom tags')
@section('crumb', 'Custom tags')
@section('content')
<h1 class="dash-title">Custom tags</h1>
<p class="text-muted mb-3">Certifications and approaches suggested by practitioners (e.g. EMDR). Approved tags become directory filters.</p>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected']])
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Tag</th><th>Suggested by</th><th>Used by</th><th></th></tr></thead>
        <tbody>
        @forelse ($tags as $t)
            <tr>
                <td>
                    <form method="POST" action="{{ route('admin.tags.update', $t) }}" class="d-flex gap-2" id="tag-{{ $t->id }}">
                        @csrf @method('PATCH')
                        <input name="name" value="{{ $t->name }}" class="form-control form-control-sm" style="max-width: 14rem">
                    </form>
                </td>
                <td class="small">{{ $t->suggester?->name ?? '—' }}</td>
                <td class="small">{{ $t->practitioners_count }}</td>
                <td class="text-end text-nowrap">
                    <button form="tag-{{ $t->id }}" name="status" value="approved" class="btn btn-sm btn-primary">Approve</button>
                    <button form="tag-{{ $t->id }}" name="status" value="rejected" class="btn btn-sm btn-paper">Reject</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Nothing here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tags->links() }}
@endsection
