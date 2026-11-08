@extends('layouts.dashboard')
@section('title', 'Articles')
@section('crumb', 'Articles')
@section('content')
<h1 class="dash-title">Articles</h1>
@include('admin.moderation._tabs', ['current' => $status->value, 'options' => ['pending' => 'In review', 'approved' => 'Published', 'rejected' => 'Sent back', 'draft' => 'Drafts']])
<div class="table-card table-responsive">
    <table class="table table-clean">
        <thead><tr><th>Title</th><th>Author</th><th>Type</th><th>Words</th><th>Submitted</th><th class="col-actions">Actions</th></tr></thead>
        <tbody>
        @forelse ($articles as $a)
            <tr>
                <td class="fw-semibold">{{ $a->title }}</td>
                <td><div class="cell-user"><x-avatar :name="$a->author->name" size="xs" round /><span class="name">{{ $a->author->name }}</span></div></td>
                <td class="small">{{ $a->type->label() }}</td>
                <td class="small">{{ number_format($a->word_count) }}</td>
                <td class="small">{{ $a->submitted_at?->diffForHumans() ?? '—' }}</td>
                <td class="col-actions"><div class="actions"><x-act icon="file-search-line" label="Review article" :href="route('admin.articles.show', $a)" /></div></td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="6"><i class="ri-article-line"></i>Nothing here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $articles->links() }}
@endsection
