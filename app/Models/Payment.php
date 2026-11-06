<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'gateway', 'gateway_reference', 'amount_minor', 'currency',
        'status', 'paid_at', 'expires_at', 'refunded_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function amountLabel(): string
    {
        return Money::format($this->amount_minor, $this->currency);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
