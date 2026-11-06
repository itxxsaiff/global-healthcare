<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use App\Services\Commission\CommissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __invoke(Request $request, CommissionService $commission): View
    {
        $profile = $request->user()->practitionerProfile;

        return view('practitioner.earnings', [
            'profile' => $profile,
            'balance' => $commission->balanceFor($profile),
            'entries' => $profile->ledgerEntries()->with('booking:id,reference,starts_at')->latest('id')->paginate(25),
        ]);
    }
}
