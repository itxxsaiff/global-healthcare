@extends('layouts.dashboard')
@section('title', 'Articles')
@section('crumb', 'Articles')
@section('content')
<h1 class="dash-title">Articles</h1>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'In review', 'approved' => 'Published', 'rejected' => 'Sent back', 'draft' => 'Drafts']])
<div class="table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Title</th><th>Author</th><th>Type</th><th>Words</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
        @forelse ($articles as $a)
            <tr>
                <td class="fw-semibold">{{ $a->title }}</td>
                <td>{{ $a->author->name }}</td>
                <td class="small">{{ $a->type->label() }}</td>
                <td class="small">{{ number_format($a->word_count) }}</td>
                <td class="small">{{ $a->submitted_at?->diffForHumans() ?? '—' }}</td>
                <td class="text-end"><a href="{{ route('admin.articles.show', $a) }}" class="btn btn-sm btn-paper">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Nothing here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $articles->links() }}
@endsection
