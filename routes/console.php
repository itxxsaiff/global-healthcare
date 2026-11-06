<?php

use App\Models\PractitionerProfile;
use App\Services\Booking\BookingService;
use App\Services\Membership\MembershipService;
use App\Services\Membership\VisibilityService;
use Illuminate\Support\Facades\Schedule;

/*
| Production cron (one line): * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
| plus a queue worker: php artisan queue:work --tries=3 (Supervisor/systemd).
*/

// Booking state machine housekeeping
Schedule::call(fn () => app(BookingService::class)->expireUnanswered())->everyFiveMinutes()->name('bookings:expire-unanswered')->withoutOverlapping();
Schedule::call(fn () => app(BookingService::class)->expireUnpaid())->everyFiveMinutes()->name('bookings:expire-unpaid')->withoutOverlapping();
Schedule::call(fn () => app(BookingService::class)->autoComplete())->everyFifteenMinutes()->name('bookings:auto-complete')->withoutOverlapping();

// Memberships
Schedule::call(fn () => app(MembershipService::class)->expireDue())->hourly()->name('memberships:expire')->withoutOverlapping();
Schedule::call(fn () => app(MembershipService::class)->sendRenewalReminders())->dailyAt('09:00')->timezone('Asia/Karachi')->name('memberships:reminders');

// Safety net: recompute directory visibility for everyone.
Schedule::call(function () {
    $visibility = app(VisibilityService::class);
    PractitionerProfile::query()->with('user')->chunkById(200, fn ($chunk) => $chunk->each(fn ($p) => $visibility->refresh($p)));
})->hourly()->name('practitioners:visibility')->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=720')->daily();
