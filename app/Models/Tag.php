<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'suggested_by'];

    protected function casts(): array
    {
        return ['status' => ModerationStatus::class];
    }

    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(PractitionerProfile::class);
    }

    public function suggester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ModerationStatus::Approved);
    }
}
