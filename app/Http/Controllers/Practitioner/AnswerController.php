<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class AnswerController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->practitionerProfile;
        $tab = $request->query('tab') === 'mine' ? 'mine' : 'open';

        $questions = Question::query()->published()->with(['specialty:id,name', 'conditions:id,name'])
            ->when($tab === 'open', fn ($q) => $q
                ->whereDoesntHave('answers', fn ($a) => $a->where('practitioner_profile_id', $profile->id))
                ->orderByRaw('specialty_id = ? desc', [$profile->primary_specialty_id])
                ->orderBy('answers_count')->latest('published_at'))
            ->when($tab === 'mine', fn ($q) => $q->whereHas('answers', fn ($a) => $a->where('practitioner_profile_id', $profile->id))->latest('published_at'))
            ->paginate(12)->withQueryString();

        return view('practitioner.questions', compact('questions', 'tab', 'profile'));
    }

    public function store(Request $request, Question $question): RedirectResponse
    {
        $profile = $request->user()->practitionerProfile;
        abort_unless($profile->is_public, 403, 'Only verified, live practitioners can answer questions.');
        abort_unless($question->status === ModerationStatus::Approved, 404);

        $data = $request->validate(['body' => ['required', 'string', 'min:80', 'max:5000']]);

        $answer = $question->answers()->where('practitioner_profile_id', $profile->id)->first()
            ?? (new Answer)->forceFill(['question_id' => $question->id, 'practitioner_profile_id' => $profile->id]);
        $answer->body = Purify::clean(nl2br(e($data['body']), false));
        $answer->status = Setting::get('answers_require_approval') ? ModerationStatus::Pending : ModerationStatus::Approved;
        $answer->save();

        $question->forceFill(['answers_count' => $question->answers()->where('status', ModerationStatus::Approved)->count()])->save();

        return back()->with('success', 'Answer published. Thank you for helping. It links back to your profile.');
    }
}
