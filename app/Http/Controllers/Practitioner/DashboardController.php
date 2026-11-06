<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $profile = $request->user()->practitionerProfile()
            ->with(['activeMembership.plan', 'primarySpecialty', 'documents', 'languages', 'availabilityRules'])
            ->firstOrFail();

        $bookings = $profile->bookings()->with('patient:id,name');

        return view('practitioner.dashboard', [
            'profile' => $profile,
            'checklist' => app(\App\Services\Verification\ProfileChecklist::class)->for($profile),
            'requests' => (clone $bookings)->where('status', BookingStatus::Requested)->oldest('starts_at')->limit(5)->get(),
            'upcoming' => (clone $bookings)->whereIn('status', [BookingStatus::Confirmed, BookingStatus::AwaitingPayment])
                ->where('starts_at', '>=', now())->oldest('starts_at')->limit(5)->get(),
            'stats' => [
                'completed' => (clone $bookings)->where('status', BookingStatus::Completed)->count(),
                'thisMonth' => (clone $bookings)->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }
}
