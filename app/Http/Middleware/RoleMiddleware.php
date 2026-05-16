<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
        public function handle(Request $request, Closure $next, $role): Response
        {

        if(!auth()->check()){
            return redirect()->route('guest_books');
            }

        if (auth()->user()->role !== $role) {
                return redirect()->route('admin_home');
                // return response(view('bawal'));
            }

            return $next($request);
        }

}
