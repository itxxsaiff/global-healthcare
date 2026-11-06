<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PractitionerProfile;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** Slot picker. Anyone can browse times; requesting needs a patient account. */
    public function create(Request $request, string $slug, SlotService $slots): View
    {
        $profile = PractitionerProfile::query()->public()->where('slug', $slug)
            ->with(['user:id,name', 'primarySpecialty', 'activeMembership.plan', 'languages'])->firstOrFail();

        $tz = $this->patientTimezone($request);

        return view('public.book', [
            'profile' => $profile,
            'days' => $slots->forPatient($profile, $tz),
            'tz' => $tz,
        ]);
    }

    public function store(Request $request, string $slug, BookingService $bookings): RedirectResponse
    {
        $profile = PractitionerProfile::query()->public()->where('slug', $slug)->with('activeMembership.plan')->firstOrFail();

        $data = $request->validate([
            'slot' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Please confirm you understand this is not an emergency service.']);

        try {
            $start = CarbonImmutable::parse($data['slot'])->utc();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['slot' => 'Please choose a time.']);
        }

        $booking = $bookings->request($request->user(), $profile, $start, $data['note'] ?? null, $this->patientTimezone($request));

        return redirect()->route('patient.bookings.show', $booking)
            ->with('success', "Request sent! {$profile->displayName()} usually replies within a few hours. We'll email you as soon as they do.");
    }

    private function patientTimezone(Request $request): string
    {
        $tz = $request->user()?->timezone;
        $cookie = $request->cookie('tz');
        if ((! $tz || $tz === 'UTC') && $cookie && in_array($cookie, \DateTimeZone::listIdentifiers(), true)) {
            $tz = $cookie;
        }

        return $tz ?: 'Asia/Karachi';
    }
}
