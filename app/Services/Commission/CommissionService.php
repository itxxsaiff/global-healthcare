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
 *  commission_reversal  −commission given back when a paid booking is refunded
 *  payable_reversal     −practitioner share reversed when a paid booking is refunded
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

    /**
     * A refunded booking gives back both sides: the platform's commission and the
     * practitioner's share, as two rows that mirror the original two exactly.
     */
    public function reverseForRefund(Booking $booking): void
    {
        $original = LedgerEntry::query()->where('booking_id', $booking->id)
            ->whereIn('type', [LedgerType::CommissionEarned, LedgerType::PractitionerPayable])
            ->pluck('amount_minor', 'type');

        $pairs = [
            LedgerType::CommissionReversal->value => [$original[LedgerType::CommissionEarned->value] ?? null, 'Refund · commission returned'],
            LedgerType::PayableReversal->value => [$original[LedgerType::PractitionerPayable->value] ?? null, 'Refund · practitioner share reversed'],
        ];

        foreach ($pairs as $type => [$amount, $note]) {
            if ($amount === null) {
                continue; // nothing was recorded for this booking (e.g. paid after it closed)
            }
            try {
                LedgerEntry::query()->create([
                    'practitioner_profile_id' => $booking->practitioner_profile_id,
                    'booking_id' => $booking->id,
                    'type' => $type,
                    'amount_minor' => -abs((int) $amount),
                    'currency' => $booking->currency,
                    'note' => $note.' · '.$booking->reference,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Already reversed: idempotent.
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

    /** What the platform still owes the practitioner, per currency (share − refunds − payouts). */
    public function balanceFor(PractitionerProfile $profile): Collection
    {
        return LedgerEntry::query()->where('practitioner_profile_id', $profile->id)
            ->whereIn('type', [...LedgerType::practitionerShare(), LedgerType::Payout])
            ->groupBy('currency')->selectRaw('currency, SUM(amount_minor) s')
            ->pluck('s', 'currency')->map(fn ($v) => (int) $v);
    }

    /**
     * Wallet summary per currency, net of refunds.
     *
     * @return Collection<string, array{fees: int, commission: int, share: int, refunded: int, paid_out: int}>
     */
    public function summary(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null, ?PractitionerProfile $profile = null): Collection
    {
        $rows = LedgerEntry::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->when($profile, fn ($q) => $q->where('practitioner_profile_id', $profile->id))
            ->groupBy('type', 'currency')->selectRaw('type, currency, SUM(amount_minor) total')->get();

        return $rows->groupBy('currency')->map(function (Collection $byType) {
            $t = fn (LedgerType $type) => (int) ($byType->first(fn ($r) => $r->type === $type)?->total ?? 0);
            $commission = $t(LedgerType::CommissionEarned) + $t(LedgerType::CommissionReversal);
            $share = $t(LedgerType::PractitionerPayable) + $t(LedgerType::PayableReversal);

            return [
                'fees' => $commission + $share,
                'commission' => $commission,
                'share' => $share,
                'refunded' => -($t(LedgerType::CommissionReversal) + $t(LedgerType::PayableReversal)),
                'paid_out' => -$t(LedgerType::Payout),
            ];
        });
    }
}
