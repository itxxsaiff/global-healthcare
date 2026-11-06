<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Membership;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Short, stable names in polymorphic columns instead of class names.
        Relation::enforceMorphMap([
            'booking' => Booking::class,
            'membership' => Membership::class,
            'user' => \App\Models\User::class,
            'practitioner' => \App\Models\PractitionerProfile::class,
            'document' => \App\Models\PractitionerDocument::class,
            'review' => \App\Models\Review::class,
            'question' => \App\Models\Question::class,
            'answer' => \App\Models\Answer::class,
            'article' => \App\Models\Article::class,
            'ticket' => \App\Models\Ticket::class,
            'payout' => \App\Models\Payout::class,
            'payment' => \App\Models\Payment::class,
            'tag' => \App\Models\Tag::class,
        ]);

        // Catch N+1 queries and silently discarded attributes during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        // Healthcare accounts: stronger passwords in production, incl. a breached-password check.
        \Illuminate\Validation\Rules\Password::defaults(fn () => $this->app->isProduction()
            ? \Illuminate\Validation\Rules\Password::min(10)->letters()->numbers()->uncompromised()
            : \Illuminate\Validation\Rules\Password::min(8));

        View::composer('layouts.dashboard', \App\View\Composers\DashboardBadges::class);
        View::composer('auth._shared-fields', fn ($view) => $view->with(
            'countries', \App\Models\Country::query()->ordered()->get(['id', 'name', 'iso2'])
        ));

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        RateLimiter::for('booking-requests', fn (Request $request) => Limit::perHour(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('questions', fn (Request $request) => Limit::perDay(5)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('tickets', fn (Request $request) => Limit::perHour(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
