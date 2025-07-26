<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated and is admin
        if (!$user || !$user->isAdmin()) {
            // For admin panel routes, show an error page or redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            // For web requests, redirect with error
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
