<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Practitioner\IntroVideoRequest;
use App\Services\Media\VideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Add, replace or remove the optional intro video shown on the public profile. */
class IntroVideoController extends Controller
{
    public function __construct(private readonly VideoService $videos) {}

    public function update(IntroVideoRequest $request): RedirectResponse|JsonResponse
    {
        $profile = $request->user()->practitionerProfile;

        if ($request->input('source') === 'upload') {
            $stored = $this->videos->storeIntro($request->file('video'), $profile->intro_video_path);
            $profile->forceFill([
                'intro_video_path' => $stored['path'],
                'intro_video_seconds' => $stored['seconds'],
                'intro_video_url' => null,
            ])->save();
        } else {
            $this->videos->delete($profile->intro_video_path);
            $profile->forceFill([
                'intro_video_url' => $request->validated('intro_video_url'),
                'intro_video_path' => null,
                'intro_video_seconds' => null,
            ])->save();
        }

        return $this->done($request, 'Intro video saved. It now appears on your public profile.');
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $profile = $request->user()->practitionerProfile()->firstOrFail();
        abort_unless($profile->verification_status->isEditable(), 403);

        $this->videos->delete($profile->intro_video_path);
        $profile->forceFill(['intro_video_url' => null, 'intro_video_path' => null, 'intro_video_seconds' => null])->save();

        return $this->done($request, 'Intro video removed.');
    }

    private function done(Request $request, string $message): RedirectResponse|JsonResponse
    {
        $url = route('practitioner.onboarding.show', ['step' => 'about']).'#intro-video';
        session()->flash('success', $message);

        return $request->expectsJson() ? response()->json(['redirect' => $url]) : redirect($url);
    }
}
