<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Account active check (Requirement 20)
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi pengelola.'], 403);
            }

            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Silakan hubungi Owner / Superadmin.');
        }

        $userRole = $user->role ?? null;

        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $allowedRoles[] = trim($r);
            }
        }

        // Superadmin bypass for any admin routes
        if ($userRole === 'superadmin' && array_intersect(['superadmin', 'owner', 'admin'], $allowedRoles)) {
            return $next($request);
        }

        if (! in_array($userRole, $allowedRoles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Anda tidak memiliki izin untuk fitur ini.'], 403);
            }

            // Redirect employee attempting to access admin routes to employee dashboard
            if ($userRole === 'employee') {
                return redirect()->route('employee.dashboard')
                    ->with('error', 'Akses ditolak. Halaman admin hanya untuk Owner, Admin, dan Superadmin.');
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki otorisasi untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
