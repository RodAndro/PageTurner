<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Redirect based on user role
        if ($user->is_admin && !$request->is('admin*')) {
            return redirect()->route('admin.dashboard');
        }

        if (!$user->is_admin && $request->is('admin*')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
