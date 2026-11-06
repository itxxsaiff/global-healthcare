<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Prices and commission are editable; changes never alter existing bookings (amounts are snapshotted). */
class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', ['plans' => Plan::query()->withCount(['memberships as active_count' => fn ($q) => $q->where('status', 'active')])->orderBy('sort')->get()]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $before = $plan->only(['price_minor', 'commission_bps', 'is_active']);
        $plan->fill([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'price_minor' => Money::toMinor($data['price']),
            'commission_bps' => (int) round($data['commission_percent'] * 100),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();
        AuditLog::record('plan.updated', null, ['plan' => $plan->code, 'before' => $before, 'after' => $plan->only(['price_minor', 'commission_bps', 'is_active'])]);

        return back()->with('success', "{$plan->name} updated. Existing bookings keep their original fee and commission.");
    }
}
