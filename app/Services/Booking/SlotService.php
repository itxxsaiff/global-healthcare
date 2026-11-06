<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\PractitionerProfile;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Generates bookable slots.
 * Availability is defined in the PRACTITIONER's time zone, slots are compared in
 * UTC, and returned grouped by day in the PATIENT's time zone (DST-safe via Carbon).
 */
class SlotService
{
    /**
     * @return array<string, list<array{start: string, label: string}>>  ['2026-11-09' => [['start' => ISO-UTC, 'label' => '4:30 PM'], …]]
     */
    public function forPatient(PractitionerProfile $profile, string $patientTz, ?int $days = null): array
    {
        $days ??= (int) Setting::get('booking_horizon_days', 30);
        $out = [];

        foreach ($this->slotsUtc($profile, $days) as $startUtc) {
            $local = $startUtc->setTimezone($patientTz);
            $out[$local->toDateString()][] = ['start' => $startUtc->toIso8601ZuluString(), 'label' => $local->format('g:i A')];
        }

        return $out;
    }

    public function isBookable(PractitionerProfile $profile, CarbonImmutable $startUtc): bool
    {
        $days = (int) Setting::get('booking_horizon_days', 30);

        foreach ($this->slotsUtc($profile, $days) as $slot) {
            if ($slot->equalTo($startUtc)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<CarbonImmutable> slot start times in UTC, free and in the future */
    public function slotsUtc(PractitionerProfile $profile, int $days): array
    {
        $tz = $profile->timezone ?: 'UTC';
        $len = max(10, (int) $profile->session_minutes);
        $earliest = CarbonImmutable::now('UTC')->addHours((int) Setting::get('min_notice_hours', 3));
        $rules = $profile->availabilityRules()->get()->groupBy('weekday');
        $exceptions = $profile->availabilityExceptions()
            ->whereBetween('date', [now($tz)->toDateString(), now($tz)->addDays($days)->toDateString()])
            ->get()->groupBy(fn ($e) => $e->date->toDateString());

        $taken = $profile->bookings()
            ->whereIn('status', BookingStatus::HOLDING)
            ->where('starts_at', '>=', now()->subDay())
            ->get(['starts_at', 'ends_at'])
            ->map(fn ($b) => [CarbonImmutable::parse($b->starts_at, 'UTC'), CarbonImmutable::parse($b->ends_at, 'UTC')]);

        $slots = [];
        $today = CarbonImmutable::now($tz)->startOfDay();

        foreach (CarbonPeriod::create($today, $today->addDays($days)) as $day) {
            $day = CarbonImmutable::instance($day);
            $dayExceptions = $exceptions->get($day->toDateString(), collect());

            if ($dayExceptions->contains(fn ($e) => $e->is_unavailable && ! $e->start_time)) {
                continue; // whole day off
            }

            $windows = $rules->get($day->dayOfWeek, collect())->map(fn ($r) => [$r->start_time, $r->end_time])->all();
            foreach ($dayExceptions->where('is_unavailable', false) as $extra) {
                $windows[] = [$extra->start_time, $extra->end_time]; // extra hours on this date
            }
            $blocked = $dayExceptions->where('is_unavailable', true)->filter(fn ($e) => $e->start_time)
                ->map(fn ($e) => [$day->setTimeFromTimeString($e->start_time), $day->setTimeFromTimeString($e->end_time)]);

            foreach ($windows as [$from, $to]) {
                $cursor = $day->setTimeFromTimeString($from);
                $end = $day->setTimeFromTimeString($to);

                while ($cursor->addMinutes($len)->lte($end)) {
                    $slotEnd = $cursor->addMinutes($len);
                    $utc = $cursor->utc();
                    $utcEnd = $slotEnd->utc();

                    $free = $utc->gte($earliest)
                        && ! $blocked->contains(fn ($b) => $cursor->lt($b[1]) && $slotEnd->gt($b[0]))
                        && ! $taken->contains(fn ($t) => $utc->lt($t[1]) && $utcEnd->gt($t[0]));

                    if ($free) {
                        $slots[$utc->timestamp] = $utc;
                    }
                    $cursor = $slotEnd;
                }
            }
        }

        ksort($slots);

        return array_values($slots);
    }
}
