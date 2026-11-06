<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $specialty = $request->filled('specialty')
            ? Specialty::query()->whereNull('parent_id')->where('slug', $request->query('specialty'))->first()
            : null;
        $q = trim((string) $request->query('q', ''));

        $questions = Question::query()->published()
            ->with(['specialty:id,name,slug', 'conditions:id,name,slug'])
            ->when($specialty, fn ($query) => $query->where('specialty_id', $specialty->id))
            ->when($request->query('filter') === 'unanswered', fn ($query) => $query->where('answers_count', 0))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('title', 'like', '%'.addcslashes($q, '%_').'%')
                ->orWhere('body', 'like', '%'.addcslashes($q, '%_').'%')))
            ->latest('published_at')
            ->paginate((int) \App\Models\Setting::get('questions_per_page', 12))
            ->withQueryString();

        return view('public.questions.index', [
            'questions' => $questions,
            'specialty' => $specialty,
            'specialties' => Specialty::tree(),
            'q' => $q,
        ]);
    }

    public function show(Question $question): View
    {
        abort_unless($question->status === \App\Enums\ModerationStatus::Approved && $question->published_at, 404);

        $question->load(['specialty', 'conditions']);
        $answers = $question->publishedAnswers()
            ->with(['practitioner' => fn ($q) => $q->with(['user:id,name', 'primarySpecialty:id,name,slug,practitioner_label'])])
            ->get();

        Question::query()->whereKey($question->id)->increment('views');

        $related = Question::query()->published()
            ->where('id', '!=', $question->id)
            ->when($question->specialty_id, fn ($q) => $q->where('specialty_id', $question->specialty_id))
            ->latest('published_at')->limit(5)->get(['id', 'title', 'slug', 'answers_count']);

        return view('public.questions.show', compact('question', 'answers', 'related'));
    }
}
