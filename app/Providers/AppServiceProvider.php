<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PractitionerDocument;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Review;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\View\Composers\DashboardBadges;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
            'user' => User::class,
            'practitioner' => PractitionerProfile::class,
            'document' => PractitionerDocument::class,
            'review' => Review::class,
            'question' => Question::class,
            'answer' => Answer::class,
            'article' => Article::class,
            'ticket' => Ticket::class,
            'payout' => Payout::class,
            'payment' => Payment::class,
            'tag' => Tag::class,
        ]);

        // Catch N+1 queries and silently discarded attributes during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        // Passwords (register, reset, change): at least 8 characters with letters and numbers,
        // the same everywhere so the on-screen hint is always true. Production also rejects
        // passwords known from data breaches.
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        View::composer('layouts.dashboard', DashboardBadges::class);
        View::composer('auth._shared-fields', fn ($view) => $view->with(
            'countries', Country::query()->ordered()->get(['id', 'name', 'iso2'])
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
