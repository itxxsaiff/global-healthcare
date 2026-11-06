<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\Specialty;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sub-specialties and symptoms are editable. The 14 top-level specialties are
 * fixed by the client document (no add/remove here, only wording).
 */
class TaxonomyController extends Controller
{
    public function index(): View
    {
        return view('admin.taxonomy.index', [
            'specialties' => Specialty::query()->whereNull('parent_id')->orderBy('sort')->with(['children' => fn ($q) => $q->orderBy('sort')])->get(),
            'conditions' => Condition::query()->with('specialties:id,name')->orderBy('name')->get(),
        ]);
    }

    public function storeSub(Request $request, Specialty $specialty): RedirectResponse
    {
        abort_unless($specialty->parent_id === null, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $specialty->children()->create([
            'name' => $data['name'],
            'slug' => Slug::unique(Specialty::class, $data['name'].' '.$specialty->practitioner_label),
            'sort' => $specialty->children()->max('sort') + 1,
        ]);

        return back()->with('success', 'Sub-specialty added.');
    }

    public function toggleSub(Specialty $specialty): RedirectResponse
    {
        abort_if($specialty->parent_id === null, 422, 'The 14 main specialties are fixed.');
        $specialty->update(['is_active' => ! $specialty->is_active]);

        return back()->with('success', $specialty->name.($specialty->is_active ? ' shown.' : ' hidden.'));
    }

    public function storeCondition(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('conditions', 'name')],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*' => [Rule::exists('specialties', 'id')->whereNull('parent_id')],
            'is_featured' => ['nullable', 'boolean'],
        ]);
        $condition = Condition::query()->create([
            'name' => $data['name'],
            'slug' => Slug::unique(Condition::class, $data['name']),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ]);
        $condition->specialties()->sync($data['specialties']);

        return back()->with('success', 'Symptom added.');
    }

    public function toggleCondition(Condition $condition): RedirectResponse
    {
        $condition->update(['is_active' => ! $condition->is_active]);

        return back()->with('success', $condition->name.($condition->is_active ? ' shown.' : ' hidden.'));
    }
}
