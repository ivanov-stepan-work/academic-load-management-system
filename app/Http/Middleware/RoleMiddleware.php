<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (Auth::check()) {
            // Check if user has one of the required roles
            foreach ($roles as $roleId) {
                foreach (Auth::user()->roles as $role) {
                    if ($role->id == $roleId) {
                        return $next($request);
                    }
                }
            }
        }

        // Redirect or abort if user does not have required role
        abort(403, 'Несанкционированное действие');
    }
}
