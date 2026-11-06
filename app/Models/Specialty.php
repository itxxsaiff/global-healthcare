<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Specialty extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'practitioner_label', 'summary', 'icon', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('specialties.tree'));
        static::deleted(fn () => Cache::forget('specialties.tree'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class);
    }

    public function practitioners(): HasMany
    {
        return $this->hasMany(PractitionerProfile::class, 'primary_specialty_id');
    }

    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id')->where('is_active', true)->orderBy('sort');
    }

    /** Cached top-level specialties with their children, used across forms and nav. */
    public static function tree()
    {
        return Cache::rememberForever('specialties.tree', fn () => self::topLevel()
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
