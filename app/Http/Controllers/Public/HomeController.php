<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\Language;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Specialty;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $data = (function () {
            $featured = PractitionerProfile::query()->public()
                ->with(['user:id,name', 'primarySpecialty:id,name,slug,practitioner_label', 'city:id,name', 'country:id,name,iso2', 'languages:id,name,code', 'conditions:id,name,slug'])
                ->orderByDesc('is_featured')->orderByDesc('rating_avg')->limit(6)->get();

            $counts = PractitionerProfile::query()->public()
                ->selectRaw('primary_specialty_id, COUNT(*) c')->groupBy('primary_specialty_id')->pluck('c', 'primary_specialty_id');

            return [
                'featured' => $featured,
                'specialtyCounts' => $counts,
                'symptoms' => Condition::query()->active()->where('is_featured', true)->orderBy('name')->get(['id', 'name', 'slug']),
                'questions' => Question::query()->published()->with('specialty:id,name')->latest('published_at')->limit(4)->get(),
                'doctorCount' => PractitionerProfile::query()->public()->count(),
            ];
        })();

        return view('public.home', $data + [
            'specialties' => Specialty::tree(),
            'languages' => Language::query()->orderBy('sort')->get(['code', 'name']),
        ]);
    }
}
