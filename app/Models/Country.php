<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['iso2', 'name', 'slug', 'currency', 'default_timezone', 'is_target', 'sort'];

    protected function casts(): array
    {
        return ['is_target' => 'boolean'];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('name');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('name');
    }

    public function isPakistan(): bool
    {
        return $this->iso2 === 'PK';
    }
}
