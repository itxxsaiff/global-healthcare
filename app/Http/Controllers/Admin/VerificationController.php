<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PractitionerProfile;
use App\Services\Media\VideoService;
use App\Services\Verification\ProfileChecklist;
use App\Services\Verification\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('status'), ['pending', 'rejected', 'draft'], true) ? $request->query('status') : 'pending';

        $profiles = PractitionerProfile::query()
            ->where('verification_status', $tab)
            ->with(['user:id,name,email,created_at', 'primarySpecialty:id,name', 'country:id,name', 'documents:id,practitioner_profile_id,type'])
            ->orderBy($tab === 'pending' ? 'submitted_at' : 'updated_at', $tab === 'pending' ? 'asc' : 'desc')
            ->paginate(20)->withQueryString();

        $counts = PractitionerProfile::query()->selectRaw('verification_status, COUNT(*) c')->groupBy('verification_status')->pluck('c', 'verification_status');

        return view('admin.verifications.index', compact('profiles', 'tab', 'counts'));
    }

    public function show(PractitionerProfile $profile, ProfileChecklist $checklist): View
    {
        $profile->load(['user', 'primarySpecialty', 'subSpecialties', 'conditions', 'languages', 'tags', 'documents', 'country', 'city', 'memberships.plan']);

        $history = AuditLog::query()
            ->where('subject_type', 'practitioner')->where('subject_id', $profile->id)
            ->with('actor:id,name')->latest('id')->limit(15)->get();

        return view('admin.verifications.show', [
            'profile' => $profile,
            'checklist' => $checklist->for($profile),
            'history' => $history,
        ]);
    }

    public function approve(Request $request, PractitionerProfile $profile): RedirectResponse
    {
        $this->verification->approve($profile, $request->user());

        return redirect()->route('admin.verifications.index')->with('success', "{$profile->user->name} is verified and has been emailed to choose a plan.");
    }

    public function reject(Request $request, PractitionerProfile $profile): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->verification->reject($profile, $request->user(), $data['reason']);

        return redirect()->route('admin.verifications.index')->with('success', "Changes requested from {$profile->user->name}.");
    }

    public function suspend(Request $request, PractitionerProfile $profile): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->verification->suspend($profile, $data['reason']);

        return back()->with('success', 'Profile suspended and hidden from the directory.');
    }

    public function reinstate(PractitionerProfile $profile): RedirectResponse
    {
        $this->verification->reinstate($profile);

        return back()->with('success', 'Profile reinstated.');
    }

    /** Take down an intro video (e.g. off-topic or unprofessional). The doctor can add a new one. */
    public function removeVideo(PractitionerProfile $profile, VideoService $videos): RedirectResponse
    {
        $videos->delete($profile->intro_video_path);
        $profile->forceFill(['intro_video_url' => null, 'intro_video_path' => null, 'intro_video_seconds' => null])->save();
        AuditLog::record('practitioner.video_removed', $profile);

        return back()->with('success', 'Intro video removed from the profile.');
    }
}
