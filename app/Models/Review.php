<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['rating', 'body'];

    protected function casts(): array
    {
        return [
            'status' => ModerationStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    /** Public display name: first name + initial, e.g. "Ayesha K." */
    public function authorLabel(): string
    {
        $parts = explode(' ', trim((string) $this->patient?->name));

        return trim(($parts[0] ?? 'Patient').' '.(isset($parts[1]) ? mb_substr($parts[1], 0, 1).'.' : ''));
    }
}
