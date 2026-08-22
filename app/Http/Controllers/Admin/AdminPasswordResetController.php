<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\OutletScopeService;
use App\Services\UserRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminPasswordResetController extends Controller
{
    public function __construct(
        protected OutletScopeService $outletScopeService,
        protected UserRoleService $userRoleService,
    ) {}

    /**
     * Handle an authorized administrative password reset for an employee login.
     */
    public function reset(Request $request, Employee $employee): RedirectResponse
    {
        $actor = $request->user();

        if (! $this->outletScopeService->canManageEmployee($actor, $employee)) {
            abort(403, 'Akses ditolak. Anda tidak berwenang mengelola akun karyawan ini.');
        }

        $targetUser = $employee->user;

        if (! $targetUser) {
            return redirect()->back()
                ->with('error', 'Karyawan ini belum memiliki akun user login terdaftar.');
        }

        if (! $this->userRoleService->canActorManageUser($actor, $targetUser)) {
            abort(403, 'Akses ditolak. Anda tidak berwenang mengelola akun pengguna ini.');
        }

        $request->validate([
            'superadmin_password' => [$actor->role === 'superadmin' ? 'required' : 'nullable', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'superadmin_password.required' => 'Konfirmasi password Superadmin Anda wajib diisi.',
            'new_password.required' => 'Password baru karyawan wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Preserve the existing re-authentication requirement for Superadmin.
        if ($actor->role === 'superadmin' && ! Hash::check($request->input('superadmin_password'), $actor->password)) {
            return redirect()->back()
                ->withErrors(['superadmin_password' => 'Konfirmasi password Superadmin salah. Otorisasi ditolak.']);
        }

        // Update password and invalidate remember token.
        $targetUser->forceFill([
            'password' => Hash::make($request->input('new_password')),
            'remember_token' => Str::random(60),
        ])->save();

        // Invalidate all active HTTP sessions for target user.
        try {
            DB::table('sessions')->where('user_id', $targetUser->id)->delete();
        } catch (\Throwable $e) {
            // Ignored if session table is not used
        }

        // Record system audit log without password material.
        AuditLog::log(
            'password_reset.admin_completed',
            $targetUser,
            null,
            [
                'admin_id' => $actor->id,
                'admin_name' => $actor->name,
                'target_employee_id' => $employee->id,
                'target_user_id' => $targetUser->id,
                'target_name' => $targetUser->name,
                'target_email' => $targetUser->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            $actor
        );

        return redirect()->back()
            ->with('success', 'Password akun ['.$targetUser->name.'] berhasil diperbarui secara administratif.');
    }
}
