<?php

namespace App\Enums;

/**
 * Commission wallet ledger (append-only). Each paid platform booking writes one
 * commission row + one practitioner row; a refund writes the two matching reversals.
 */
enum LedgerType: string
{
    /** Platform's share of a paid Starter-plan booking (20% by default). */
    case CommissionEarned = 'commission_earned';
    /** Amount owed to the practitioner (fee minus commission, 80% by default). */
    case PractitionerPayable = 'practitioner_payable';
    /** Money transferred to the practitioner (negative). */
    case Payout = 'payout';
    /** Refund: the platform's commission given back (negative). */
    case CommissionReversal = 'commission_reversal';
    /** Refund: the practitioner's share reversed (negative). */
    case PayableReversal = 'payable_reversal';
    /** @deprecated Old single-row refund (−full fee); converted by the 2026_11_15 migration. */
    case RefundReversal = 'refund_reversal';

    /** Rows that make up the platform's net commission. */
    public static function commission(): array
    {
        return [self::CommissionEarned, self::CommissionReversal];
    }

    /** Rows that make up what a practitioner is owed (before payouts). */
    public static function practitionerShare(): array
    {
        return [self::PractitionerPayable, self::PayableReversal];
    }

    public function label(): string
    {
        return match ($this) {
            self::CommissionEarned => 'Commission earned',
            self::PractitionerPayable => 'Practitioner share',
            self::Payout => 'Paid out',
            self::CommissionReversal => 'Refund · commission returned',
            self::PayableReversal => 'Refund · practitioner share reversed',
            self::RefundReversal => 'Refunded',
        };
    }
}
