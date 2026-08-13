<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    /**
     * Check if an active Superadmin exists in the application.
     */
    public static function hasActiveSuperadmin(): bool
    {
        return User::where('role', 'superadmin')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Show the First-Run Setup form.
     */
    public function showForm(): View|RedirectResponse
    {
        if (self::hasActiveSuperadmin()) {
            return redirect()->route('login')
                ->with('info', 'Initial setup telah selesai. Silakan login dengan akun Superadmin.');
        }

        return view('auth.setup');
    }

    /**
     * Process initial Superadmin creation & application initialization.
     */
    public function processSetup(Request $request): RedirectResponse
    {
        if (self::hasActiveSuperadmin()) {
            return redirect()->route('login')
                ->with('error', 'Setup telah dikunci. Superadmin aktif sudah terdaftar di sistem.');
        }

        $request->validate([
            'app_name' => ['nullable', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap Superadmin wajib diisi.',
            'name.max' => 'Nama maksimal 150 karakter.',
            'email.required' => 'Email Superadmin wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Race condition check with row-level lock
                $alreadyExists = User::where('role', 'superadmin')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyExists) {
                    throw new \RuntimeException('Setup telah diselesaikan oleh sesi lain.');
                }

                if ($request->filled('app_name')) {
                    AppSetting::set('app_name', trim($request->input('app_name')), 'string', true);
                }

                if ($request->filled('company_name')) {
                    AppSetting::set('company_name', trim($request->input('company_name')), 'string', true);
                }

                // Force server-side attributes (ignore any forged request input like role, is_active, employee_id)
                $superadmin = User::create([
                    'name' => trim($request->input('name')),
                    'email' => strtolower(trim($request->input('email'))),
                    'password' => Hash::make($request->input('password')),
                    'role' => 'superadmin',
                    'is_active' => true,
                    'employee_id' => null,
                ]);

                // Record system audit log
                AuditLog::log(
                    'system.first_superadmin_created',
                    $superadmin,
                    null,
                    [
                        'user_id' => $superadmin->id,
                        'name' => $superadmin->name,
                        'email' => $superadmin->email,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                    null
                );
            });

            return redirect()->route('login')
                ->with('success', 'Superadmin pertama berhasil dibuat. Silakan login.');
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Gagal memproses setup: ' . $e->getMessage());
        }
    }
}
