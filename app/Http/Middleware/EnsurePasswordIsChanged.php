<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * Redirects users who need to change their password to the change password page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not authenticated
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip if already on change password page or logging out
        if ($request->routeIs('password.change.form') ||
            $request->routeIs('password.change') ||
            $request->routeIs('logout')) {
            return $next($request);
        }

        // Redirect if password must be changed
        if ($request->session()->get('must_change_password')) {
            return redirect()->route('password.change.form')
                ->with('warning', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}
