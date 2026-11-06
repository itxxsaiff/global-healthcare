<?php

namespace App\Http\Controllers\Public;

use App\Enums\ArticleStatus;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\PractitionerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PractitionerController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $profile = PractitionerProfile::query()->where('slug', $slug)->firstOrFail();

        // Hidden profiles are visible only to their owner (preview) and admins.
        $user = $request->user();
        $isPreview = ! $profile->is_public;
        abort_if($isPreview && ! ($user?->isAdmin() || $user?->id === $profile->user_id), 404);

        $profile->load([
            'user:id,name',
            'primarySpecialty', 'subSpecialties', 'conditions', 'languages', 'approvedTags',
            'city', 'country', 'activeMembership.plan', 'availabilityRules',
        ]);

        $reviews = $profile->approvedReviews()->with('patient:id,name')->limit(10)->get();
        $answers = $profile->answers()->where('status', ModerationStatus::Approved)
            ->whereHas('question', fn ($q) => $q->published())
            ->with('question:id,title,slug')->latest()->limit(5)->get();
        $articles = $profile->articles()->where('status', ArticleStatus::Approved)->latest('published_at')->limit(3)->get();

        return view('public.practitioner', compact('profile', 'reviews', 'answers', 'articles', 'isPreview'));
    }
}
