<?php

namespace App\Services\Commission;

use App\Enums\LedgerType;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\PractitionerProfile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * The "Admin Wallet" (client doc §6 step 4). Append-only ledger:
 *  commission_earned   +platform share of a paid Plan 1 booking
 *  practitioner_payable +what we owe the practitioner (fee − commission)
 *  payout               −money transferred to the practitioner
 *  refund_reversal      −reverses both sides when a paid booking is refunded
 */
class CommissionService
{
    public function recordPaidBooking(Booking $booking): void
    {
        $rows = [
            [LedgerType::CommissionEarned, $booking->commission_minor, 'Commission '.($booking->commission_bps / 100).'%'],
            [LedgerType::PractitionerPayable, $booking->practitioner_earning_minor, 'Practitioner share'],
        ];

        foreach ($rows as [$type, $amount, $note]) {
            try {
                LedgerEntry::query()->create([
                    'practitioner_profile_id' => $booking->practitioner_profile_id,
                    'booking_id' => $booking->id,
                    'type' => $type,
                    'amount_minor' => $amount,
                    'currency' => $booking->currency,
                    'note' => $note.' · '.$booking->reference,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Already recorded for this booking: idempotent.
            }
        }
    }

    public function reverseForRefund(Booking $booking): void
    {
        $paid = LedgerEntry::query()->where('booking_id', $booking->id)
            ->whereIn('type', [LedgerType::CommissionEarned, LedgerType::PractitionerPayable])->sum('amount_minor');

        if ($paid > 0) {
            try {
                LedgerEntry::query()->create([
                    'practitioner_profile_id' => $booking->practitioner_profile_id,
                    'booking_id' => $booking->id,
                    'type' => LedgerType::RefundReversal,
                    'amount_minor' => -$booking->fee_minor,
                    'currency' => $booking->currency,
                    'note' => 'Refund · '.$booking->reference,
                ]);
            } catch (UniqueConstraintViolationException) {
            }
        }
    }

    public function recordPayout(Payout $payout): void
    {
        LedgerEntry::query()->create([
            'practitioner_profile_id' => $payout->practitioner_profile_id,
            'payout_id' => $payout->id,
            'type' => LedgerType::Payout,
            'amount_minor' => -$payout->amount_minor,
            'currency' => $payout->currency,
            'note' => 'Payout '.$payout->method.($payout->reference ? ' · '.$payout->reference : ''),
        ]);
    }

    /** What the platform still owes the practitioner, per currency. */
    public function balanceFor(PractitionerProfile $profile): Collection
    {
        $payable = LedgerEntry::query()->where('practitioner_profile_id', $profile->id)
            ->where('type', LedgerType::PractitionerPayable)->groupBy('currency')
            ->selectRaw('currency, SUM(amount_minor) s')->pluck('s', 'currency');
        $paidOut = LedgerEntry::query()->where('practitioner_profile_id', $profile->id)
            ->where('type', LedgerType::Payout)->groupBy('currency')
            ->selectRaw('currency, SUM(amount_minor) s')->pluck('s', 'currency');
        $refunds = LedgerEntry::query()->where('ledger_entries.practitioner_profile_id', $profile->id)
            ->where('ledger_entries.type', LedgerType::RefundReversal)->join('bookings', 'bookings.id', '=', 'ledger_entries.booking_id')
            ->groupBy('ledger_entries.currency')
            ->selectRaw('ledger_entries.currency, SUM(bookings.practitioner_earning_minor) s')->pluck('s', 'currency');

        return $payable->keys()->merge($paidOut->keys())->unique()->mapWithKeys(fn ($c) => [
            $c => (int) ($payable[$c] ?? 0) + (int) ($paidOut[$c] ?? 0) - (int) ($refunds[$c] ?? 0),
        ]);
    }
}
