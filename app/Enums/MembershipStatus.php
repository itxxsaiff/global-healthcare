<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Active => 'Active',
            self::PastDue => 'Payment overdue',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::PendingPayment, self::PastDue => 'warning',
            self::Cancelled, self::Expired => 'neutral',
        };
    }
}
