<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Plan;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        return view('public.page', compact('page'));
    }

    public function forPractitioners(): View
    {
        $plans = Plan::query()->active()->get()->groupBy(fn ($p) => $p->market->value);

        return view('public.for-practitioners', compact('plans'));
    }
}
