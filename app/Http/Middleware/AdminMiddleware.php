<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in? send to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Allow either a 'role' column == 'admin' OR an 'is_admin' boolean
        $isAdmin = ($user->role ?? null) === 'admin' || (bool) ($user->is_admin ?? false);

        if (!$isAdmin) {
            // You can change this to redirect()->route('dashboard') if you prefer
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
