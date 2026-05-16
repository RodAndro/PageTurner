<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // If user has 2FA enabled but hasn't completed verification this session
        if ($user->two_factor_secret && !session('two_factor_authenticated')) {
            // Allow access to 2FA routes
            if ($request->routeIs('two-factor.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
