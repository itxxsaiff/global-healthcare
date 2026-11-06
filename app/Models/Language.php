<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'is_preset', 'sort'];

    protected function casts(): array
    {
        return ['is_preset' => 'boolean'];
    }

    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(PractitionerProfile::class);
    }
}
