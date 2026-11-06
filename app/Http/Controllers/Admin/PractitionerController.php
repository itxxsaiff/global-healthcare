<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PractitionerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PractitionerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $profiles = PractitionerProfile::query()
            ->with(['user:id,name,email,status', 'primarySpecialty:id,name', 'country:id,name', 'activeMembership.plan:id,name,code'])
            ->when($q !== '', fn ($query) => $query->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($request->query('market'), fn ($query, $m) => $query->where('market', $m))
            ->when($request->query('visibility') === 'public', fn ($query) => $query->where('is_public', true))
            ->when($request->query('visibility') === 'hidden', fn ($query) => $query->where('is_public', false))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.practitioners.index', compact('profiles', 'q'));
    }
}
