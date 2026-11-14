<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Services\Commission\CommissionService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** The "Admin Wallet": commission earned and what each Plan 1 practitioner is owed. */
class WalletController extends Controller
{
    public function index(Request $request, CommissionService $commission): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = ($request->date('to') ?? now())->endOfDay();

        // Practitioners with an outstanding balance (their share, minus refunds and payouts).
        $owedQuery = LedgerEntry::query()
            ->join('practitioner_profiles', 'practitioner_profiles.id', '=', 'ledger_entries.practitioner_profile_id')
            ->join('users', 'users.id', '=', 'practitioner_profiles.user_id')
            ->whereIn('ledger_entries.type', [...LedgerType::practitionerShare(), LedgerType::Payout])
            ->groupBy('ledger_entries.practitioner_profile_id', 'ledger_entries.currency', 'users.name')
            ->selectRaw('ledger_entries.practitioner_profile_id, ledger_entries.currency, users.name, SUM(ledger_entries.amount_minor) balance')
            ->havingRaw('SUM(ledger_entries.amount_minor) <> 0')
            ->orderByDesc('balance');

        return view('admin.wallet.index', [
            'from' => $from, 'to' => $to,
            'summary' => $commission->summary($from, $to),
            'commissionRate' => Plan::query()->where('code', 'pk_free')->value('commission_bps'),
            'owed' => (clone $owedQuery)->paginate(10, ['*'], 'owed_page')->withQueryString(),
            // Everyone with a balance, for the "Record a payout" dropdown.
            'payees' => (clone $owedQuery)->get()->unique('practitioner_profile_id'),
            'recent' => LedgerEntry::query()->with(['practitioner.user:id,name', 'booking:id,reference'])->latest('id')
                ->paginate(25, ['*'], 'ledger_page')->withQueryString(),
        ]);
    }

    public function storePayout(Request $request, CommissionService $commission): RedirectResponse
    {
        $data = $request->validate([
            'practitioner_profile_id' => ['required', 'exists:practitioner_profiles,id'],
            'currency' => ['required', 'in:PKR,USD'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'max:40'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        $profile = PractitionerProfile::query()->findOrFail($data['practitioner_profile_id']);
        $balance = (int) ($commission->balanceFor($profile)[$data['currency']] ?? 0);
        $amount = Money::toMinor($data['amount']);
        abort_if($amount > $balance, 422, 'Payout is larger than the amount owed ('.Money::format($balance, $data['currency']).').');

        DB::transaction(function () use ($data, $amount, $request, $commission) {
            $payout = Payout::query()->create([
                'practitioner_profile_id' => $data['practitioner_profile_id'],
                'amount_minor' => $amount,
                'currency' => $data['currency'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'created_by' => $request->user()->id,
            ]);
            $commission->recordPayout($payout);
            AuditLog::record('payout.recorded', $payout, ['amount' => $amount, 'currency' => $data['currency']]);
        });

        return back()->with('success', 'Payout of '.Money::format($amount, $data['currency']).' recorded.');
    }
}
