<?php

namespace App\Models;

use App\Enums\BookingFlow;
use App\Enums\BookingStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    /** Status and money columns are set only by BookingService, never mass-assigned. */
    protected $fillable = ['patient_note', 'meeting_details'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'flow' => BookingFlow::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'responded_at' => 'datetime',
            'respond_by' => 'datetime',
            'payment_due_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'patient_note' => 'encrypted',
            'meeting_details' => 'encrypted',
            'refund_due' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function latestPayment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function feeLabel(): string
    {
        return Money::format($this->fee_minor, $this->currency);
    }

    public function startsAtFor(string $timezone): Carbon
    {
        return $this->starts_at->copy()->setTimezone($timezone);
    }

    public function needsPayment(): bool
    {
        return $this->status === BookingStatus::AwaitingPayment && $this->flow === BookingFlow::PlatformPayment;
    }

    public function isUpcoming(): bool
    {
        return $this->status->holdsSlot() && $this->starts_at->isFuture();
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
