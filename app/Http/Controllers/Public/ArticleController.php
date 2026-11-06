<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $specialty = $request->filled('topic')
            ? Specialty::query()->whereNull('parent_id')->where('slug', $request->query('topic'))->first()
            : null;

        $articles = Article::query()->published()
            ->with(['practitioner' => fn ($q) => $q->with('user:id,name'), 'author:id,name', 'specialty:id,name,slug'])
            ->when($specialty, fn ($q) => $q->where('specialty_id', $specialty->id))
            ->latest('published_at')
            ->paginate(9)->withQueryString();

        return view('public.articles.index', [
            'articles' => $articles,
            'specialty' => $specialty,
            'specialties' => Specialty::tree(),
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->status === \App\Enums\ArticleStatus::Approved && $article->published_at?->isPast(), 404);

        $article->load(['practitioner' => fn ($q) => $q->with(['user:id,name', 'primarySpecialty']), 'author:id,name', 'specialty']);

        $more = Article::query()->published()->where('id', '!=', $article->id)
            ->when($article->specialty_id, fn ($q) => $q->where('specialty_id', $article->specialty_id))
            ->latest('published_at')->limit(3)->get();

        return view('public.articles.show', compact('article', 'more'));
    }
}
