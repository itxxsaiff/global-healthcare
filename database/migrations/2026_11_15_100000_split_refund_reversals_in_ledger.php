<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refunds used to be one ledger row of −(full fee), which mixed the platform's commission
 * with the practitioner's share. Split each into the two rows that mirror the original
 * commission/payable rows, so every total is a plain SUM by type.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $legacy = DB::table('ledger_entries')->where('type', 'refund_reversal')->get();

            foreach ($legacy as $row) {
                $original = DB::table('ledger_entries')->where('booking_id', $row->booking_id)
                    ->whereIn('type', ['commission_earned', 'practitioner_payable'])->pluck('amount_minor', 'type');
                $reference = DB::table('bookings')->where('id', $row->booking_id)->value('reference');

                foreach (['commission_earned' => ['commission_reversal', 'Refund · commission returned'], 'practitioner_payable' => ['payable_reversal', 'Refund · practitioner share reversed']] as $from => [$type, $note]) {
                    if (! isset($original[$from])) {
                        continue;
                    }
                    $exists = DB::table('ledger_entries')->where('booking_id', $row->booking_id)->where('type', $type)->exists();
                    if (! $exists) {
                        DB::table('ledger_entries')->insert([
                            'practitioner_profile_id' => $row->practitioner_profile_id,
                            'booking_id' => $row->booking_id,
                            'type' => $type,
                            'amount_minor' => -abs((int) $original[$from]),
                            'currency' => $row->currency,
                            'note' => $note.' · '.$reference,
                            'created_at' => $row->created_at,
                        ]);
                    }
                }

                DB::table('ledger_entries')->where('id', $row->id)->delete();
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $bookings = DB::table('ledger_entries')->whereIn('type', ['commission_reversal', 'payable_reversal'])->distinct()->pluck('booking_id');

            foreach ($bookings as $bookingId) {
                $rows = DB::table('ledger_entries')->where('booking_id', $bookingId)->whereIn('type', ['commission_reversal', 'payable_reversal'])->get();
                $first = $rows->first();
                DB::table('ledger_entries')->insert([
                    'practitioner_profile_id' => $first->practitioner_profile_id,
                    'booking_id' => $bookingId,
                    'type' => 'refund_reversal',
                    'amount_minor' => (int) $rows->sum('amount_minor'),
                    'currency' => $first->currency,
                    'note' => 'Refund · '.DB::table('bookings')->where('id', $bookingId)->value('reference'),
                    'created_at' => $first->created_at,
                ]);
                DB::table('ledger_entries')->whereIn('id', $rows->pluck('id'))->delete();
            }
        });
    }
};
