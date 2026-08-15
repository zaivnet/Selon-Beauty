<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeePortalAccess
{
    /**
     * Handle an incoming request for Employee Portal (/app/*).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $role = $user->role ?? null;

        // 1. Pure Administrative Roles (admin, superadmin) -> Always redirect to Admin Dashboard
        if (in_array($role, ['admin', 'superadmin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        // 2. Owner Role:
        if ($role === 'owner') {
            // Administrative-only Owner (without an Employee profile) -> Redirect to Admin Dashboard
            if (! $user->employee) {
                return redirect()->route('admin.dashboard');
            }

            // Dual-capability Owner (with an Employee profile) -> Allowed access to Employee Portal
            return $next($request);
        }

        // 3. Employee Role: Always allowed to access Employee Portal
        if ($role === 'employee') {
            return $next($request);
        }

        // Fallback for any unhandled role
        return redirect()->route('admin.dashboard');
    }
}
