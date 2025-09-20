<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // If user not logged in, redirect to login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // If the logged-in user’s role is not in the allowed list, deny access
        if (! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Forbidden');
        }

        // Otherwise allow the request through
        return $next($request);
    }
}
