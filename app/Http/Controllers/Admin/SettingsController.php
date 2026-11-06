<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class SettingsController extends Controller
{
    /** key => [label, help, rules] */
    public const FIELDS = [
        'platform_whatsapp' => ['Platform WhatsApp number', 'Used for the site-wide enquiry button and Plan 1 profiles.', ['required', 'string', 'max:30']],
        'support_email' => ['Support email', 'Shown in the footer and used as reply-to.', ['required', 'email']],
        'request_response_hours' => ['Hours to accept a request', 'Requests expire after this.', ['required', 'integer', 'min:1', 'max:72']],
        'payment_window_hours' => ['Hours to pay after acceptance', 'Unpaid bookings are released after this.', ['required', 'integer', 'min:1', 'max:72']],
        'min_notice_hours' => ['Minimum notice (hours)', 'Earliest bookable slot from now.', ['required', 'integer', 'min:0', 'max:72']],
        'booking_horizon_days' => ['Booking horizon (days)', 'How far ahead patients can book.', ['required', 'integer', 'min:7', 'max:90']],
        'max_open_requests_per_patient' => ['Max open requests per patient', 'Prevents slot-blocking.', ['required', 'integer', 'min:1', 'max:10']],
        'free_cancellation_hours' => ['Free cancellation (hours before)', 'Patients cancelling earlier than this get a full refund.', ['required', 'integer', 'min:0', 'max:168']],
        'membership_grace_days' => ['Grace period (days)', 'After a failed/expired paid plan, before hiding the profile.', ['required', 'integer', 'min:0', 'max:30']],
        'document_max_kb' => ['Document upload limit (KB)', '3072 = 3 MB, 5120 = 5 MB.', ['required', 'integer', 'min:1024', 'max:10240']],
    ];

    public function edit(): View
    {
        return view('admin.settings', ['fields' => self::FIELDS]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(collect(self::FIELDS)->map(fn ($f) => $f[2])->all());
        foreach ($data as $key => $value) {
            Setting::put($key, is_numeric($value) && ! str_starts_with((string) $value, '+') ? (int) $value : $value);
        }
        AuditLog::record('settings.updated', null, $data);

        return back()->with('success', 'Settings saved.');
    }

    public function pages(): View
    {
        return view('admin.pages', ['pages' => Page::query()->orderBy('title')->get()]);
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'body' => ['required', 'string', 'max:100000'],
        ]);
        $page->update(['title' => $data['title'], 'meta_description' => $data['meta_description'], 'body' => Purify::clean($data['body'])]);
        AuditLog::record('page.updated', null, ['page' => $page->slug]);

        return back()->with('success', $page->title.' saved.');
    }

    public function audit(Request $request): View
    {
        return view('admin.audit', [
            'logs' => \App\Models\AuditLog::query()->with('actor:id,name')
                ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
                ->latest('id')->paginate(50)->withQueryString(),
        ]);
    }
}
