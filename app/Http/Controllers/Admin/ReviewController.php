<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Content\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = ModerationStatus::tryFrom((string) $request->query('status')) ?? ModerationStatus::Pending;

        return view('admin.moderation.reviews', [
            'status' => $status,
            'reviews' => Review::query()->where('status', $status)
                ->with(['patient:id,name', 'practitioner' => fn ($q) => $q->with('user:id,name'), 'booking:id,reference,starts_at'])
                ->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $reviews->moderate($review, $request->user(), ModerationStatus::from($data['status']));

        return back()->with('success', 'Review '.$data['status'].'.');
    }
}
