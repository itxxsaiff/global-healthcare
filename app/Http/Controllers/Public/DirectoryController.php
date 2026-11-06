<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Condition;
use App\Models\Country;
use App\Models\Language;
use App\Models\Specialty;
use App\Models\Tag;
use App\Services\Search\PractitionerSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function __construct(private readonly PractitionerSearch $search) {}

    public function index(Request $request): View
    {
        return $this->render($request);
    }

    /** /dermatologists */
    public function specialty(Request $request, string $specialty): View
    {
        $model = Specialty::query()->whereNull('parent_id')->where('slug', $specialty)->where('is_active', true)->firstOrFail();

        return $this->render($request, ['specialty' => $model]);
    }

    /** /dermatologists/lahore */
    public function specialtyCity(Request $request, string $specialty, string $city): View
    {
        $model = Specialty::query()->whereNull('parent_id')->where('slug', $specialty)->where('is_active', true)->firstOrFail();
        $cityModel = City::query()->where('slug', $city)->with('country')->firstOrFail();

        return $this->render($request, ['specialty' => $model, 'city' => $cityModel, 'country' => $cityModel->country]);
    }

    /** /symptom/acne */
    public function condition(Request $request, Condition $condition): View
    {
        abort_unless($condition->is_active, 404);

        return $this->render($request, ['condition' => $condition]);
    }

    public function suggest(Request $request): JsonResponse
    {
        return response()->json($this->search->suggest((string) $request->query('q', '')))
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function render(Request $request, array $fixed = []): View
    {
        $filters = $this->search->normalise($request->query(), $fixed);
        $results = $this->search->paginate($filters);

        return view('public.directory', [
            'filters' => $filters,
            'fixed' => $fixed,
            'results' => $results,
            'specialties' => Specialty::tree(),
            'languages' => Language::query()->orderBy('sort')->get(['code', 'name']),
            'countries' => Country::query()->ordered()->where('iso2', '!=', 'ZZ')->get(['id', 'name', 'iso2']),
            'cities' => $filters['country'] ? $filters['country']->cities()->where('is_active', true)->get(['id', 'name', 'slug']) : collect(),
            'tags' => Tag::query()->approved()->orderBy('name')->get(['name', 'slug']),
            'conditions' => Condition::query()->active()->orderBy('name')->get(['name', 'slug']),
            'sorts' => PractitionerSearch::SORTS,
        ]);
    }
}
