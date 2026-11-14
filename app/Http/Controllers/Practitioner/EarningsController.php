<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Services\Commission\CommissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Starter-plan earnings: each paid consultation split into platform commission and the practitioner's share. */
class EarningsController extends Controller
{
    public function __invoke(Request $request, CommissionService $commission): View
    {
        $profile = $request->user()->practitionerProfile;

        return view('practitioner.earnings', [
            'profile' => $profile,
            'balance' => $commission->balanceFor($profile),
            'summary' => $commission->summary(profile: $profile),
            // The practitioner's own statement: their share, refunds of it and payouts (not the platform's rows).
            'entries' => $profile->ledgerEntries()
                ->whereIn('type', [...LedgerType::practitionerShare(), LedgerType::Payout])
                ->with('booking:id,reference,starts_at,fee_minor,commission_minor,commission_bps,currency')
                ->latest('id')->paginate(25),
        ]);
    }
}
