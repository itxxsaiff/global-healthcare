@extends('layouts.dashboard')
@section('title', 'Custom tags')
@section('crumb', 'Custom tags')
@section('content')
<h1 class="dash-title">Custom tags</h1>
<p class="text-muted mb-3">Certifications and approaches suggested by practitioners (e.g. EMDR). Approved tags become directory filters.</p>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected']])
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Tag</th><th>Suggested by</th><th>Used by</th><th class="col-actions">Actions</th></tr></thead>
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
                <td class="col-actions"><div class="actions">
                    <x-act form="tag-{{ $t->id }}" name="status" value="approved" type="submit" icon="check-line" label="Approve" tone="success" />
                    <x-act form="tag-{{ $t->id }}" name="status" value="rejected" type="submit" icon="close-line" label="Reject" tone="danger" />
                </div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="4"><i class="ri-price-tag-3-line"></i>Nothing here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tags->links() }}
@endsection
