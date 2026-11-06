<?php

namespace App\Enums;

enum LedgerType: string
{
    /** Platform's 20% share of a paid Plan 1 booking. */
    case CommissionEarned = 'commission_earned';
    /** Amount owed to the practitioner (fee minus commission). */
    case PractitionerPayable = 'practitioner_payable';
    /** Money transferred to the practitioner (negative). */
    case Payout = 'payout';
    /** Reversal when a paid booking is refunded (negative). */
    case RefundReversal = 'refund_reversal';
}
