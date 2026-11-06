<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = ['practitioner_profile_id', 'amount_minor', 'currency', 'method', 'reference', 'note', 'paid_at', 'created_by'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'practitioner_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amountLabel(): string
    {
        return Money::format($this->amount_minor, $this->currency);
    }
}
