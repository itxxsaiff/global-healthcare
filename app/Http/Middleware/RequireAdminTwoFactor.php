<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admins must confirm two-factor authentication before using the admin panel. */
class RequireAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (app()->isProduction() && $user?->isAdmin() && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('account.edit')
                ->with('warning', 'Please turn on two-factor authentication before using the admin panel.');
        }

        return $next($request);
    }
}
