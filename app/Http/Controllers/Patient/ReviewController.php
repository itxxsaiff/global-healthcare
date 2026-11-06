<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Content\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function create(Request $request, Booking $booking): View
    {
        abort_unless($this->reviews->canReview($request->user(), $booking), 404);

        return view('patient.review', ['booking' => $booking->load('practitioner.user')]);
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:1500'],
        ]);

        $this->reviews->submit($request->user(), $booking, (int) $data['rating'], $data['body'] ?? null);

        return redirect()->route('patient.bookings.show', $booking)
            ->with('success', 'Thank you! Your review will appear once our team has checked it.');
    }
}
