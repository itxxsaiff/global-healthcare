@extends('layouts.dashboard')
@section('title', 'Reviews')
@section('crumb', 'Reviews')
@section('content')
<h1 class="dash-title">Reviews</h1>
<p class="text-muted mb-3">Every review comes from a completed, paid consultation. Approve the ones that are fair and appropriate.</p>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'Waiting', 'approved' => 'Published', 'rejected' => 'Rejected']])
@forelse ($reviews as $r)
    <div class="card-soft p-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div><x-rating :value="$r->rating" /> <span class="small text-muted ms-2">{{ $r->patient->name }} → {{ $r->practitioner->displayName() }} · {{ $r->booking->reference }}</span></div>
            <small class="text-muted">{{ $r->created_at->diffForHumans() }}</small>
        </div>
        <p class="mb-3">{{ $r->body ?: '(no written feedback)' }}</p>
        <div class="d-flex gap-2">
            @if ($r->status !== \App\Enums\ModerationStatus::Approved)
                <form method="POST" action="{{ route('admin.reviews.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-primary"><i class="ri-check-line"></i> Approve</button></form>
            @endif
            @if ($r->status !== \App\Enums\ModerationStatus::Rejected)
                <form method="POST" action="{{ route('admin.reviews.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-danger"><i class="ri-close-line"></i> Reject</button></form>
            @endif
        </div>
    </div>
@empty
    <x-empty icon="star" title="Nothing waiting" />
@endforelse
{{ $reviews->links() }}
@endsection
