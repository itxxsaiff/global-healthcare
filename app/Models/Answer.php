<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = ['body'];

    protected function casts(): array
    {
        return ['status' => ModerationStatus::class];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }
}
