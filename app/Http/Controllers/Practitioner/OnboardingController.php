<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\DocumentType;
use App\Enums\ModerationStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Practitioner\AboutRequest;
use App\Http\Requests\Practitioner\DetailsRequest;
use App\Http\Requests\Practitioner\DocumentsRequest;
use App\Http\Requests\Practitioner\FocusRequest;
use App\Models\Condition;
use App\Models\Language;
use App\Models\PractitionerProfile;
use App\Models\Specialty;
use App\Models\Tag;
use App\Services\Media\DocumentStore;
use App\Services\Media\ImageService;
use App\Services\Verification\ProfileChecklist;
use App\Services\Verification\VerificationService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Practitioner profile wizard: details → focus → about → documents → review & submit.
 * Once approved, the same screens are used to edit the profile.
 */
class OnboardingController extends Controller
{
    public const STEPS = [
        'details' => 'Professional details',
        'focus' => 'Specialties & languages',
        'about' => 'Bio, photo & video',
        'documents' => 'Documents',
        'review' => 'Review & submit',
    ];

    public function __construct(
        private readonly ProfileChecklist $checklist,
        private readonly VerificationService $verification,
    ) {}

    public function show(Request $request): View
    {
        $profile = $this->profile($request)->load(['country.cities', 'subSpecialties', 'conditions', 'languages', 'tags', 'documents', 'primarySpecialty']);
        $step = array_key_exists($request->query('step'), self::STEPS) ? $request->query('step') : $this->firstIncompleteStep($profile);

        return view('practitioner.onboarding', [
            'profile' => $profile,
            'step' => $step,
            'steps' => self::STEPS,
            'checklist' => $this->checklist->for($profile),
            'editable' => $profile->verification_status->isEditable(),
            'specialties' => Specialty::tree(),
            'conditions' => Condition::query()->active()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->orderBy('sort')->get(['id', 'name']),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'documentTypes' => DocumentType::cases(),
        ]);
    }

    public function details(DetailsRequest $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validated();
        $licenceChanged = $profile->license_number !== null && $profile->license_number !== $data['license_number'];

        if ((int) $profile->primary_specialty_id !== (int) $data['primary_specialty_id']) {
            $profile->subSpecialties()->detach(); // sub-specialties belong to the old parent
        }

        $profile->fill([
            ...collect($data)->except('fee')->all(),
            'fee_minor' => Money::toMinor($data['fee']),
        ])->save();

        if ($licenceChanged) {
            $this->verification->requireReverification($profile, 'licence number');
        }

        return $this->next('details', 'Professional details saved.');
    }

    public function focus(FocusRequest $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validated();

        $profile->subSpecialties()->sync($data['sub_specialties'] ?? []);
        $profile->conditions()->sync($data['conditions'] ?? []);
        $profile->languages()->sync($data['languages']);
        $profile->forceFill(['other_languages' => $data['other_languages'] ?? null])->save();

        // Custom certifications (e.g. EMDR, Art Therapy) go into the admin approval queue.
        $names = collect(explode(',', (string) ($data['custom_tags'] ?? '')))
            ->map(fn ($t) => Str::of($t)->squish()->limit(40, '')->toString())->filter()->unique()->take(5);
        $tagIds = $names->map(function ($name) use ($request) {
            return Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => ModerationStatus::Pending, 'suggested_by' => $request->user()->id],
            )->id;
        });
        $profile->tags()->sync($tagIds);

        return $this->next('focus', 'Specialties and languages saved.');
    }

    public function about(AboutRequest $request, ImageService $images): RedirectResponse
    {
        $profile = $this->profile($request);
        $profile->fill($request->safe()->only(['headline', 'bio']));

        if ($request->hasFile('headshot')) {
            $profile->headshot_path = $images->storeHeadshot($request->file('headshot'), $profile->headshot_path);
        }
        $profile->save();

        return $this->next('about', 'Bio and photo saved.');
    }

    public function documents(DocumentsRequest $request, DocumentStore $store): RedirectResponse
    {
        $profile = $this->profile($request);

        foreach (DocumentType::cases() as $type) {
            if ($request->hasFile($type->value)) {
                $store->store($profile, $type, $request->file($type->value));
            }
        }

        $this->verification->requireReverification($profile, 'documents');

        return $this->next('documents', 'Documents uploaded securely.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $this->verification->submit($this->profile($request));

        return redirect()->route('practitioner.dashboard')
            ->with('success', "Thanks! Your profile is with our verification team. We'll email you as soon as it's reviewed.");
    }

    private function profile(Request $request): PractitionerProfile
    {
        return $request->user()->practitionerProfile()->firstOrFail();
    }

    private function next(string $current, string $message): RedirectResponse
    {
        $keys = array_keys(self::STEPS);
        $next = $keys[array_search($current, $keys, true) + 1] ?? 'review';

        return redirect()->route('practitioner.onboarding.show', ['step' => $next])->with('success', $message);
    }

    private function firstIncompleteStep(PractitionerProfile $profile): string
    {
        if ($profile->verification_status !== VerificationStatus::Draft) {
            return 'review';
        }
        foreach ($this->checklist->for($profile) as $item) {
            if (! $item['done']) {
                return $item['step'];
            }
        }

        return 'review';
    }
}
