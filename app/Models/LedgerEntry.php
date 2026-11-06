<?php

namespace App\Models;

use App\Enums\LedgerType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only. Never update or delete rows; write a reversing entry instead. */
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['practitioner_profile_id', 'booking_id', 'payout_id', 'type', 'amount_minor', 'currency', 'note'];

    protected function casts(): array
    {
        return ['type' => LedgerType::class];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function amountLabel(): string
    {
        return Money::format($this->amount_minor, $this->currency);
    }
}
