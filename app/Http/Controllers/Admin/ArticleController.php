<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use App\Notifications\ContentDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $status = ArticleStatus::tryFrom((string) $request->query('status')) ?? ArticleStatus::Pending;

        return view('admin.moderation.articles', [
            'status' => $status,
            'articles' => Article::query()->where('status', $status)
                ->with(['author:id,name', 'specialty:id,name'])
                ->latest('submitted_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Article $article): View
    {
        return view('admin.moderation.article', ['article' => $article->load(['author', 'practitioner', 'specialty'])]);
    }

    /** One-click approve (client doc), or send back with a note. */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000'],
        ]);
        $approve = $data['decision'] === 'approve';

        $article->forceFill([
            'status' => $approve ? ArticleStatus::Approved : ArticleStatus::Rejected,
            'published_at' => $approve ? ($article->published_at ?? now()) : $article->published_at,
            'rejection_reason' => $approve ? null : $data['reason'],
            'reviewed_by' => $request->user()->id,
        ])->save();

        AuditLog::record('article.'.($approve ? 'approved' : 'rejected'), $article);
        $article->author->notify(new ContentDecision('article', $article->title, $approve,
            $approve ? route('articles.show', $article) : route('practitioner.articles.edit', $article), $data['reason'] ?? null));

        return redirect()->route('admin.articles.index')->with('success', $approve ? 'Article published.' : 'Sent back to the author.');
    }
}
