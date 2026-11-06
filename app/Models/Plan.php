<?php

namespace App\Models;

use App\Enums\Market;
use App\Enums\PlanInterval;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'tagline', 'market', 'currency', 'price_minor', 'interval',
        'commission_bps', 'shows_linkedin', 'direct_dealing', 'stripe_price_id', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'market' => Market::class,
            'interval' => PlanInterval::class,
            'shows_linkedin' => 'boolean',
            'direct_dealing' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort');
    }

    public function isFree(): bool
    {
        return $this->price_minor === 0;
    }

    public function priceLabel(): string
    {
        return $this->isFree() ? 'Free' : Money::format($this->price_minor, $this->currency);
    }

    public function commissionPercent(): float
    {
        return $this->commission_bps / 100;
    }
}
