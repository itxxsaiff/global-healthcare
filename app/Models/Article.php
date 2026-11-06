<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id', 'type', 'title', 'slug', 'excerpt', 'body', 'word_count',
        'featured_image_path', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'type' => ArticleType::class,
            'status' => ArticleStatus::class,
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Approved)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function imageUrl(): ?string
    {
        return $this->featured_image_path ? Storage::disk('public')->url($this->featured_image_path) : null;
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->word_count / 220));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
