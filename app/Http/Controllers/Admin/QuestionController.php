<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModerationStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ContentDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $status = ModerationStatus::tryFrom((string) $request->query('status')) ?? ModerationStatus::Pending;

        return view('admin.moderation.questions', [
            'status' => $status,
            'questions' => Question::query()->where('status', $status)
                ->with(['specialty:id,name', 'patient:id,name', 'answers' => fn ($q) => $q->with(['practitioner' => fn ($p) => $p->with('user:id,name')])])
                ->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'title' => ['nullable', 'string', 'min:10', 'max:180'],
        ]);
        $approved = $data['status'] === 'approved';

        $question->forceFill([
            'status' => $data['status'],
            'published_at' => $approved ? ($question->published_at ?? now()) : null,
            'moderated_by' => $request->user()->id,
        ]);
        if (filled($data['title'] ?? null)) {
            $question->title = $data['title']; // admin may tidy the title before publishing
        }
        $question->save();

        AuditLog::record('question.'.$data['status'], $question);
        $question->patient->notify(new ContentDecision('question', $question->title, $approved,
            $approved ? route('questions.show', $question) : route('patient.questions.index'),
            $approved ? null : 'Questions must be general health questions without personal identifying details.'));

        return back()->with('success', 'Question '.$data['status'].'.');
    }

    /** Hide or restore a doctor's answer. */
    public function answer(Request $request, Answer $answer): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $answer->forceFill(['status' => $data['status']])->save();
        $answer->question->forceFill(['answers_count' => $answer->question->answers()->where('status', ModerationStatus::Approved)->count()])->save();
        AuditLog::record('answer.'.$data['status'], $answer);

        return back()->with('success', $data['status'] === 'approved' ? 'Answer restored.' : 'Answer hidden.');
    }
}
