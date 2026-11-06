<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    /** Generate a slug unique within $model's table (soft-deleted rows included). */
    public static function unique(string $model, string $source, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($source, 80, '')) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        /** @var Model $instance */
        $instance = new $model;
        $query = fn (string $s) => $instance->newQueryWithoutScopes()
            ->where($column, $s)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        while ($query($slug)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
