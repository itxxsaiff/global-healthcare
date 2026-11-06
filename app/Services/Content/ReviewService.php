<?php

namespace App\Services\Content;

use App\Enums\BookingStatus;
use App\Enums\ModerationStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\PractitionerProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Client doc: only patients with a Completed / Paid booking through the platform
 * can review; every review is moderated; the average updates automatically.
 */
class ReviewService
{
    public function canReview(User $patient, Booking $booking): bool
    {
        if ($booking->patient_id !== $patient->id || $booking->status !== BookingStatus::Completed || $booking->review()->exists()) {
            return false;
        }

        // Platform-paid bookings must have a successful payment; direct-dealing bookings
        // (paid plans) count once the session is completed through the platform.
        return $booking->flow === \App\Enums\BookingFlow::Direct
            || $booking->payments()->where('status', PaymentStatus::Paid)->exists();
    }

    public function submit(User $patient, Booking $booking, int $rating, ?string $body): Review
    {
        if (! $this->canReview($patient, $booking)) {
            throw ValidationException::withMessages(['rating' => 'You can only review a completed consultation you booked, once.']);
        }

        $review = new Review(['rating' => $rating, 'body' => $body]);
        $review->forceFill([
            'booking_id' => $booking->id,
            'patient_id' => $patient->id,
            'practitioner_profile_id' => $booking->practitioner_profile_id,
            'status' => ModerationStatus::Pending,
        ])->save();

        return $review;
    }

    public function moderate(Review $review, User $admin, ModerationStatus $status): void
    {
        $review->forceFill(['status' => $status, 'moderated_by' => $admin->id, 'moderated_at' => now()])->save();
        AuditLog::record('review.'.$status->value, $review);
        $this->recalculate($review->practitioner);
    }

    public function recalculate(PractitionerProfile $profile): void
    {
        $stats = Review::query()->where('practitioner_profile_id', $profile->id)->where('status', ModerationStatus::Approved)
            ->selectRaw('AVG(rating) avg, COUNT(*) c')->first();

        $profile->forceFill([
            'rating_avg' => round((float) $stats->avg, 2),
            'reviews_count' => (int) $stats->c,
        ])->save();
    }
}
