<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Membership\VisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.users.index', [
            'users' => User::query()
                ->when($request->query('role'), fn ($query, $r) => $query->where('role', $r))
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
                ->with('practitionerProfile:id,user_id,slug,headshot_path')
                ->latest()->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function toggle(Request $request, User $user, VisibilityService $visibility): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot suspend yourself.');

        $user->forceFill(['status' => $user->isActive() ? UserStatus::Suspended : UserStatus::Active])->save();
        AuditLog::record('user.'.$user->status->value, $user);
        if ($user->practitionerProfile) {
            $visibility->refresh($user->practitionerProfile);
        }

        return back()->with('success', $user->name.' is now '.$user->status->value.'.');
    }
}
