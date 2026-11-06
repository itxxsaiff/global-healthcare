<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Membership extends Model
{
    protected $fillable = [
        'plan_id', 'status', 'starts_at', 'ends_at', 'auto_renew',
        'gateway', 'gateway_subscription_id', 'gateway_customer_id', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function daysLeft(): ?int
    {
        return $this->ends_at ? max(0, (int) now()->diffInDays($this->ends_at, false)) : null;
    }
}
