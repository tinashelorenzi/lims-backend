<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip if already on account setup routes or logout
        if ($request->routeIs(['account.setup', 'account.setup.store', 'logout'])) {
            return $next($request);
        }

        // Skip for API routes (let them handle setup via API)
        if ($request->is('api/*')) {
            return $next($request);
        }

        // Redirect to account setup if needed
        if ($user->needsAccountSetup()) {
            return redirect()->route('account.setup')
                ->with('warning', 'Please complete your account setup to continue.');
        }

        return $next($request);
    }
}