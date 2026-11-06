<?php

namespace App\Http\Controllers\Patient;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\Question;
use App\Models\Specialty;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Patients ask anonymously; questions are moderated before publishing (client doc §7). */
class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        return view('patient.questions.index', [
            'questions' => $request->user()->questions()->with('specialty:id,name')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('patient.questions.create', [
            'specialties' => Specialty::tree(),
            'conditions' => Condition::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'specialty_id' => ['required', Rule::exists('specialties', 'id')->whereNull('parent_id')],
            'title' => ['required', 'string', 'min:15', 'max:180'],
            'body' => ['required', 'string', 'min:40', 'max:3000'],
            'conditions' => ['nullable', 'array', 'max:3'],
            'conditions.*' => ['integer', Rule::exists('conditions', 'id')],
            'share_profile' => ['nullable', 'boolean'],
        ]);

        $patient = $request->user()->loadMissing('patientProfile');
        $question = new Question([
            'specialty_id' => $data['specialty_id'],
            'title' => $data['title'],
            'slug' => Slug::unique(Question::class, $data['title']),
            'body' => $data['body'],
            // Optional context ("Female, 28") helps doctors answer; never the name.
            'asker_gender' => ($data['share_profile'] ?? false) ? $patient->patientProfile?->gender?->value : null,
            'asker_age' => ($data['share_profile'] ?? false) ? $patient->patientProfile?->age() : null,
        ]);
        $question->forceFill(['patient_id' => $patient->id, 'status' => ModerationStatus::Pending])->save();
        $question->conditions()->sync($data['conditions'] ?? []);

        return redirect()->route('patient.questions.index')
            ->with('success', 'Question received. It will be published anonymously once our team has checked it, and verified practitioners will be notified.');
    }
}
