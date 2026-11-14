<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\NoStoreForAuthenticated;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'verified', 'role:patient'])
                ->prefix('patient')->name('patient.')
                ->group(base_path('routes/patient.php'));

            Route::middleware(['web', 'auth', 'verified', 'role:practitioner'])
                ->prefix('practitioner')->name('practitioner.')
                ->group(base_path('routes/practitioner.php'));

            Route::middleware(['web', 'auth', 'verified', 'role:admin', 'admin.2fa'])
                ->prefix('admin')->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Payment gateway callbacks: no session/CSRF, signature-verified in the controller.
            Route::middleware(['api', 'throttle:webhooks'])
                ->prefix('webhooks')->name('webhooks.')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'admin.2fa' => RequireAdminTwoFactor::class,
        ]);

        $middleware->web(append: [
            EnsureUserIsActive::class,
            NoStoreForAuthenticated::class,
            SecurityHeaders::class,
        ]);

        // Gateway return URLs may POST back from the provider's domain.
        $middleware->validateCsrfTokens(except: ['payments/*/return/*']);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'webhooks/*') || $request->expectsJson(),
        );

        // Upload bigger than the server's post_max_size: explain it instead of a bare 413.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'That file is too large for the server. Please upload a smaller or compressed file.'], 413);
            }
        });
    })->create();
