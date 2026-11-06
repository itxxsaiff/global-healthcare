<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['specialty_id', 'title', 'slug', 'body', 'asker_gender', 'asker_age'];

    /** The asker is never exposed publicly. */
    protected $hidden = ['patient_id'];

    protected function casts(): array
    {
        return [
            'status' => ModerationStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function publishedAnswers(): HasMany
    {
        return $this->answers()->where('status', ModerationStatus::Approved)->oldest();
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', ModerationStatus::Approved)->whereNotNull('published_at');
    }

    public function askerLabel(): string
    {
        $bits = array_filter([
            $this->asker_gender ? ucfirst(str_replace('_', ' ', $this->asker_gender)) : null,
            $this->asker_age ? $this->asker_age.' yrs' : null,
        ]);

        return $bits ? 'Anonymous · '.implode(', ', $bits) : 'Anonymous patient';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
