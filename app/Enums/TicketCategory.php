<?php

namespace App\Enums;

enum TicketCategory: string
{
    case NoShow = 'no_show';
    case Technical = 'technical';
    case PaymentDispute = 'payment_dispute';
    case UnprofessionalBehavior = 'unprofessional_behavior';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NoShow => 'No-show',
            self::Technical => 'Technical glitch',
            self::PaymentDispute => 'Payment dispute',
            self::UnprofessionalBehavior => 'Unprofessional behaviour',
            self::Other => 'Something else',
        };
    }
}
