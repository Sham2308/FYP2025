<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage examples in routes:
     *   ->middleware('role:technical')
     *   ->middleware('role:technical,admin')
     *   ->middleware('role:technical|admin')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Not logged in → go to login page
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // If roles came as a single string, split by comma or pipe
        if (count($roles) === 1 && is_string($roles[0])) {
            $roles = preg_split('/[,\|]/', $roles[0]) ?: [];
        }

        // Normalize roles (trim + lowercase, remove empties)
        $roles = array_values(array_filter(array_map(
            fn ($r) => strtolower(trim((string) $r)),
            $roles
        )));

        // If nothing specified, deny by default
        if (empty($roles)) {
            abort(403, 'Forbidden');
        }

        // Compare using lowercase
        $userRole = strtolower((string) $request->user()->role);

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
