<?php

namespace App\Services\Booking;

use App\Enums\BookingFlow;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PractitionerProfile;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingUpdate;
use App\Services\Commission\CommissionService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Two-step booking workflow (client doc §6):
 *   patient requests slot → practitioner accepts → payment link → patient pays
 *   → verified → confirmed → alerts to practitioner, admin and patient → commission recorded.
 *
 * Direct-dealing plans (Plan 2 / international) skip the payment step.
 * Every transition: DB transaction + row lock + allowed-transition check + history row.
 */
class BookingService
{
    public function __construct(
        private readonly SlotService $slots,
        private readonly CommissionService $commission,
    ) {}

    public function request(User $patient, PractitionerProfile $profile, CarbonImmutable $startUtc, ?string $note, string $patientTz): Booking
    {
        if (! $patient->isPatient()) {
            throw ValidationException::withMessages(['slot' => 'Only patient accounts can book consultations.']);
        }
        if (! $profile->is_public) {
            throw ValidationException::withMessages(['slot' => 'This practitioner is not taking bookings right now.']);
        }

        $open = $patient->bookings()->whereIn('status', [BookingStatus::Requested, BookingStatus::AwaitingPayment])->count();
        if ($open >= (int) Setting::get('max_open_requests_per_patient', 3)) {
            throw ValidationException::withMessages(['slot' => "You already have {$open} open requests. Please wait for a reply or cancel one first."]);
        }

        if (! $this->slots->isBookable($profile, $startUtc)) {
            throw ValidationException::withMessages(['slot' => 'Sorry, that time is no longer available. Please pick another.']);
        }

        $plan = $profile->activeMembership?->plan;
        $direct = (bool) $plan?->direct_dealing;
        $bps = $direct ? 0 : (int) ($plan?->commission_bps ?? 0);
        $commission = Money::percentOf((int) $profile->fee_minor, $bps);

        try {
            $booking = DB::transaction(function () use ($patient, $profile, $startUtc, $note, $patientTz, $plan, $direct, $bps, $commission) {
                $booking = new Booking(['patient_note' => $note ? Str::limit($note, 1000, '') : null]);
                $booking->forceFill([
                    'reference' => $this->newReference(),
                    'patient_id' => $patient->id,
                    'practitioner_profile_id' => $profile->id,
                    'plan_id' => $plan?->id,
                    'starts_at' => $startUtc,
                    'ends_at' => $startUtc->addMinutes($profile->session_minutes),
                    'practitioner_timezone' => $profile->timezone,
                    'patient_timezone' => $patientTz,
                    'status' => BookingStatus::Requested,
                    'flow' => $direct ? BookingFlow::Direct : BookingFlow::PlatformPayment,
                    'fee_minor' => (int) $profile->fee_minor,
                    'currency' => $profile->fee_currency,
                    'commission_bps' => $bps,
                    'commission_minor' => $commission,
                    'practitioner_earning_minor' => (int) $profile->fee_minor - $commission,
                    'respond_by' => min(now()->addHours((int) Setting::get('request_response_hours', 24)), $startUtc->subHour()),
                ])->save();

                $this->history($booking, null, BookingStatus::Requested, $patient);

                return $booking;
            });
        } catch (UniqueConstraintViolationException) {
            // Two patients clicked the same slot at the same moment: the unique index decided.
            throw ValidationException::withMessages(['slot' => 'Someone has just booked that time. Please pick another.']);
        }

        $profile->user->notify(new BookingUpdate($booking, 'requested_practitioner'));

        return $booking;
    }

    public function accept(Booking $booking, User $practitioner, ?string $meetingDetails = null): Booking
    {
        $booking = $this->transition($booking, function (Booking $b) use ($meetingDetails) {
            if ($meetingDetails) {
                $b->meeting_details = $meetingDetails;
            }
            $b->responded_at = now();

            if ($b->flow === BookingFlow::Direct) {
                $b->confirmed_at = now();

                return BookingStatus::Confirmed;
            }

            $b->payment_due_at = min(
                now()->addHours((int) Setting::get('payment_window_hours', 12)),
                CarbonImmutable::parse($b->starts_at)->subMinutes(30),
            );

            return BookingStatus::AwaitingPayment;
        }, $practitioner);

        $booking->patient->notify(new BookingUpdate($booking, $booking->status === BookingStatus::Confirmed ? 'confirmed_patient' : 'accepted_patient'));

        return $booking;
    }

    public function reject(Booking $booking, User $practitioner, ?string $reason = null): Booking
    {
        $booking = $this->transition($booking, function (Booking $b) use ($reason) {
            $b->responded_at = now();
            $b->cancellation_reason = $reason;

            return BookingStatus::Rejected;
        }, $practitioner, $reason);

        $booking->patient->notify(new BookingUpdate($booking, 'declined_patient'));

        return $booking;
    }

    /** Called by PaymentService (inside its transaction) once the gateway confirms payment. */
    public function paymentReceived(Booking $booking, Payment $payment): void
    {
        $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

        if ($locked->status === BookingStatus::Confirmed) {
            return; // idempotent
        }

        if (! in_array($locked->status, [BookingStatus::AwaitingPayment, BookingStatus::PaymentExpired], true)) {
            // Paid for a booking that was cancelled meanwhile: never lose the money silently.
            $locked->forceFill(['refund_due' => true])->save();
            AuditLog::record('booking.paid_after_close', $locked, ['payment' => $payment->uuid]);
            DB::afterCommit(fn () => $this->notifyAdmins($locked, 'refund_needed_admin'));

            return;
        }

        $from = $locked->status;
        try {
            DB::transaction(function () use ($locked) {
                $locked->forceFill(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()])->save();
            });
        } catch (UniqueConstraintViolationException) {
            // Late payment, and the slot was taken after it expired. Discard the
            // rolled-back in-memory status before flagging the refund.
            $locked->refresh()->forceFill(['refund_due' => true])->save();
            AuditLog::record('booking.late_payment_slot_taken', $locked, ['payment' => $payment->uuid]);
            DB::afterCommit(fn () => $this->notifyAdmins($locked, 'refund_needed_admin'));

            return;
        }

        $this->history($locked, $from, BookingStatus::Confirmed, null, 'Payment '.$payment->uuid.' verified');
        $this->commission->recordPaidBooking($locked);

        DB::afterCommit(function () use ($locked) {
            $locked->refresh();
            $locked->patient->notify(new BookingUpdate($locked, 'confirmed_patient'));
            $locked->practitioner->user->notify(new BookingUpdate($locked, 'paid_practitioner'));
            $this->notifyAdmins($locked, 'paid_admin');
        });
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        $byPatient = $actor->id === $booking->patient_id;
        $byPractitioner = $actor->id === $booking->practitioner->user_id;
        abort_unless($byPatient || $byPractitioner || $actor->isAdmin(), 403);

        $booking = $this->transition($booking, function (Booking $b) use ($byPatient, $actor, $reason) {
            $paid = $b->payments()->where('status', PaymentStatus::Paid)->exists();
            $hoursBefore = now()->diffInHours($b->starts_at, false);

            $b->cancelled_at = now();
            $b->cancelled_by = $actor->id;
            $b->cancellation_reason = $reason;
            // Refund rules (docs/DECISIONS.md #11): practitioner/admin cancel → refund; patient ≥ 24h before → refund.
            $b->refund_due = $paid && (! $byPatient || $hoursBefore >= (int) Setting::get('free_cancellation_hours', 24));

            return $byPatient ? BookingStatus::CancelledByPatient : BookingStatus::CancelledByPractitioner;
        }, $actor, $reason);

        $other = $byPatient ? $booking->practitioner->user : $booking->patient;
        $other->notify(new BookingUpdate($booking, 'cancelled_other'));
        if ($booking->refund_due) {
            $this->notifyAdmins($booking, 'refund_needed_admin');
        }

        return $booking;
    }

    public function complete(Booking $booking, ?User $actor = null): Booking
    {
        if ($booking->starts_at->isFuture()) {
            throw ValidationException::withMessages(['booking' => 'A session can only be completed after it has started.']);
        }

        return $this->transition($booking, function (Booking $b) {
            $b->completed_at = now();

            return BookingStatus::Completed;
        }, $actor);
    }

    // ── Scheduled housekeeping (routes/console.php) ─────────────────

    public function expireUnanswered(): int
    {
        return $this->sweep(
            Booking::query()->where('status', BookingStatus::Requested)->where('respond_by', '<', now()),
            BookingStatus::ExpiredUnanswered, 'expired_unanswered_patient',
        );
    }

    public function expireUnpaid(): int
    {
        return $this->sweep(
            Booking::query()->where('status', BookingStatus::AwaitingPayment)->where('payment_due_at', '<', now()),
            BookingStatus::PaymentExpired, 'payment_expired_patient',
        );
    }

    public function autoComplete(): int
    {
        $count = 0;
        Booking::query()->where('status', BookingStatus::Confirmed)
            ->where('ends_at', '<', now()->subHours((int) Setting::get('auto_complete_after_hours', 2)))
            ->each(function (Booking $b) use (&$count) {
                $this->complete($b);
                $count++;
            });

        return $count;
    }

    // ── internals ────────────────────────────────────────────────

    /** @param callable(Booking): BookingStatus $mutate */
    private function transition(Booking $booking, callable $mutate, ?User $actor, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($booking, $mutate, $actor, $note) {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;
            $to = $mutate($locked);

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'booking' => "This booking is \"{$from->label()}\" and can't be changed to \"{$to->label()}\".",
                ]);
            }

            $locked->status = $to;
            $locked->save();
            $this->history($locked, $from, $to, $actor, $note);

            return $locked;
        });
    }

    private function sweep($query, BookingStatus $to, string $notice): int
    {
        $count = 0;
        $query->each(function (Booking $b) use ($to, $notice, &$count) {
            try {
                $b = $this->transition($b, fn () => $to, null, 'Expired automatically');
                $b->patient->notify(new BookingUpdate($b, $notice));
                $count++;
            } catch (ValidationException) {
                // Status changed in the meantime: nothing to do.
            }
        });

        return $count;
    }

    private function history(Booking $booking, ?BookingStatus $from, BookingStatus $to, ?User $actor, ?string $note = null): void
    {
        $booking->history()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'note' => $note ? Str::limit($note, 480) : null,
        ]);
    }

    private function notifyAdmins(Booking $booking, string $kind): void
    {
        Notification::send(User::query()->where('role', Role::Admin)->get(), new BookingUpdate($booking, $kind));
    }

    private function newReference(): string
    {
        do {
            $ref = 'GH-'.Str::upper(Str::random(6));
        } while (Booking::query()->where('reference', $ref)->exists());

        return $ref;
    }
}
