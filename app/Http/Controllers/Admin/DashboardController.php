<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\LedgerType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\PractitionerProfile;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();

        return view('admin.dashboard', [
            'stats' => [
                'public' => PractitionerProfile::query()->public()->count(),
                'pending' => PractitionerProfile::query()->where('verification_status', VerificationStatus::Pending)->count(),
                'patients' => User::query()->where('role', 'patient')->count(),
                'bookingsMonth' => Booking::query()->where('created_at', '>=', $monthStart)->count(),
                'awaitingResponse' => Booking::query()->where('status', BookingStatus::Requested)->count(),
            ],
            'commission' => LedgerEntry::query()
                ->whereIn('type', LedgerType::commission())->where('created_at', '>=', $monthStart) // net of refunds
                ->selectRaw('currency, SUM(amount_minor) total')->groupBy('currency')->pluck('total', 'currency'),
            'queue' => PractitionerProfile::query()
                ->where('verification_status', VerificationStatus::Pending)
                ->with(['user:id,name,email', 'primarySpecialty:id,name', 'country:id,name'])
                ->oldest('submitted_at')->limit(6)->get(),
            'recentBookings' => Booking::query()
                ->with(['patient:id,name', 'practitioner' => fn ($q) => $q->with('user:id,name')])
                ->latest()->limit(8)->get(),
        ]);
    }
}
