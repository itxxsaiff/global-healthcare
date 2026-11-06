<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Custom specialties / certifications suggested by practitioners (client doc: "entering Admin queue"). */
class TagController extends Controller
{
    public function index(Request $request): View
    {
        $status = ModerationStatus::tryFrom((string) $request->query('status')) ?? ModerationStatus::Pending;

        return view('admin.moderation.tags', [
            'status' => $status,
            'tags' => Tag::query()->where('status', $status)->with('suggester:id,name')->withCount('practitioners')->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'name' => ['nullable', 'string', 'max:40'],
        ]);
        $tag->forceFill(['status' => $data['status']] + (filled($data['name'] ?? null) ? ['name' => $data['name']] : []))->save();
        AuditLog::record('tag.'.$data['status'], $tag);

        return back()->with('success', "\"{$tag->name}\" {$data['status']}.");
    }
}
