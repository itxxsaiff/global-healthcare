<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityException extends Model
{
    protected $fillable = ['date', 'is_unavailable', 'start_time', 'end_time', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_unavailable' => 'boolean'];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }
}
