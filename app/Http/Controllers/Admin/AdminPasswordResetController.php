<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminPasswordResetController extends Controller
{
    /**
     * Handle Superadmin administrative password reset for a target user.
     */
    public function reset(Request $request, Employee $employee): RedirectResponse
    {
        // Enforce Superadmin-only policy
        $this->authorizeSuperadmin();

        $request->validate([
            'superadmin_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'superadmin_password.required' => 'Konfirmasi password Superadmin Anda wajib diisi.',
            'new_password.required' => 'Password baru karyawan wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $superadmin = Auth::user();

        // 1. Re-authenticate Superadmin
        if (! Hash::check($request->input('superadmin_password'), $superadmin->password)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['superadmin_password' => 'Konfirmasi password Superadmin salah. Otorisasi ditolak.']);
        }

        // 2. Find target user account linked to employee
        $targetUser = User::where('employee_id', $employee->id)
            ->orWhere('email', $employee->email)
            ->first();

        if (! $targetUser) {
            return redirect()->back()
                ->with('error', 'Karyawan ini belum memiliki akun user login terdaftar.');
        }

        // 3. Update password and invalidate remember token
        $targetUser->forceFill([
            'password' => Hash::make($request->input('new_password')),
            'remember_token' => Str::random(60),
        ])->save();

        // 4. Invalidate all active HTTP sessions for target user
        try {
            DB::table('sessions')->where('user_id', $targetUser->id)->delete();
        } catch (\Throwable $e) {
            // Ignored if session table is not used
        }

        // 5. Record System Audit Log
        AuditLog::log(
            'password_reset.admin_completed',
            $targetUser,
            null,
            [
                'admin_id' => $superadmin->id,
                'admin_name' => $superadmin->name,
                'target_user_id' => $targetUser->id,
                'target_name' => $targetUser->name,
                'target_email' => $targetUser->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            $superadmin
        );

        return redirect()->back()
            ->with('success', 'Password akun [' . $targetUser->name . '] berhasil diperbarui secara administratif.');
    }

    /**
     * Ensure current user is a Superadmin.
     */
    private function authorizeSuperadmin(): void
    {
        if (! Auth::check() || Auth::user()->role !== 'superadmin') {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Superadmin.');
        }
    }
}
