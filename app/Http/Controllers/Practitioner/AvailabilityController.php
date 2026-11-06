<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->practitionerProfile->load(['availabilityRules']);
        $exceptions = $profile->availabilityExceptions()->where('date', '>=', now($profile->timezone)->toDateString())->orderBy('date')->get();

        return view('practitioner.availability', [
            'profile' => $profile,
            'rules' => $profile->availabilityRules->groupBy('weekday')->map(fn ($g) => $g->map(fn ($r) => ['start' => substr($r->start_time, 0, 5), 'end' => substr($r->end_time, 0, 5)])->values()),
            'exceptions' => $exceptions,
        ]);
    }

    /** Replaces the weekly schedule. Times are in the practitioner's own time zone. */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['array'],
            'days.*' => ['array', 'max:4'],
            'days.*.*.start' => ['required', 'date_format:H:i'],
            'days.*.*.end' => ['required', 'date_format:H:i'],
        ]);

        $rows = [];
        foreach ($data['days'] ?? [] as $weekday => $windows) {
            abort_unless(in_array((int) $weekday, range(0, 6), true), 422);
            $sorted = collect($windows)->sortBy('start')->values();
            foreach ($sorted as $i => $w) {
                if ($w['end'] <= $w['start']) {
                    throw ValidationException::withMessages(['days' => 'Each time window must end after it starts.']);
                }
                if ($i > 0 && $w['start'] < $sorted[$i - 1]['end']) {
                    throw ValidationException::withMessages(['days' => 'Time windows on the same day must not overlap.']);
                }
                $rows[] = ['weekday' => (int) $weekday, 'start_time' => $w['start'], 'end_time' => $w['end']];
            }
        }

        $profile = $request->user()->practitionerProfile;
        DB::transaction(function () use ($profile, $rows) {
            $profile->availabilityRules()->delete();
            foreach ($rows as $row) {
                $profile->availabilityRules()->create($row);
            }
        });

        return back()->with('success', 'Weekly availability saved. Existing bookings are not affected.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $profile = $request->user()->practitionerProfile;
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', 'in:off,extra'],
            'start_time' => ['nullable', 'required_if:type,extra', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_if:type,extra', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $profile->availabilityExceptions()->create([
            'date' => $data['date'],
            'is_unavailable' => $data['type'] === 'off',
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', $data['type'] === 'off' ? 'Day off added.' : 'Extra hours added.');
    }

    public function destroyException(Request $request, int $id): RedirectResponse
    {
        $request->user()->practitionerProfile->availabilityExceptions()->whereKey($id)->delete();

        return back()->with('success', 'Removed.');
    }
}
