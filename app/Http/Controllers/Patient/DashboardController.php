<?php

namespace App\Http\Controllers\Patient;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $base = $user->bookings()->with(['practitioner' => fn ($q) => $q->with(['user:id,name', 'primarySpecialty:id,practitioner_label'])]);

        return view('patient.dashboard', [
            'user' => $user,
            'needsPayment' => (clone $base)->where('status', BookingStatus::AwaitingPayment)->oldest('payment_due_at')->get(),
            'upcoming' => (clone $base)->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed])
                ->where('starts_at', '>=', now())->oldest('starts_at')->limit(5)->get(),
            'toReview' => (clone $base)->where('status', BookingStatus::Completed)->whereDoesntHave('review')
                ->latest('starts_at')->limit(3)->get(),
        ]);
    }
}
