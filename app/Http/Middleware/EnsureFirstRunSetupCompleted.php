<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\SetupController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstRunSetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always allow public static assets, PWA manifest, service worker, health check, login, and password reset routes
        if ($request->is('up', 'sw.js', 'offline.html', 'manifest.webmanifest', 'login', 'forgot-password', 'reset-password*') || $request->routeIs('login', 'password.*', 'pwa.manifest', 'offline')) {
            return $next($request);
        }

        // If database schema is not migrated yet, pass through
        try {
            if (! Schema::hasTable('users')) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            return $next($request);
        }

        $hasSuperadmin = SetupController::hasActiveSuperadmin();

        // 1. If active Superadmin EXISTS
        if ($hasSuperadmin) {
            // Lock /setup endpoint from further access
            if ($request->routeIs('setup', 'setup.*')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Setup telah dikunci.'], 403);
                }

                return redirect()->route('login')
                    ->with('info', 'Initial setup telah selesai. Silakan login.');
            }

            return $next($request);
        }

        // 2. If NO active Superadmin exists
        // Allow access to /setup routes
        if ($request->routeIs('setup', 'setup.*')) {
            return $next($request);
        }

        // Do not interrupt already authenticated sessions
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Aplikasi belum diinisialisasi. Buka /setup untuk membuat Superadmin pertama.'], 503);
        }

        // Redirect unauthenticated root '/' and admin requests to /setup when no active Superadmin exists
        if ($request->is('/') || $request->is('admin*')) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
