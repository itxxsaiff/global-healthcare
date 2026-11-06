<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PractitionerDocument extends Model
{
    protected $fillable = ['type', 'path', 'original_name', 'mime', 'size_bytes', 'sha256', 'status', 'note'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => ModerationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function sizeLabel(): string
    {
        return number_format($this->size_bytes / 1048576, 1).' MB';
    }
}
