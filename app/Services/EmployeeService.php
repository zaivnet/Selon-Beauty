<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(protected ?UserRoleService $userRoleService = null)
    {
        $this->userRoleService = $userRoleService ?? new UserRoleService;
    }

    /**
     * Generate auto-suggested unique employee code (e.g. SB-001, SB-002).
     */
    public function generateNextEmployeeCode(): string
    {
        $lastEmployee = Employee::withTrashed()
            ->where('employee_code', 'LIKE', 'SB-%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastEmployee) {
            return 'SB-001';
        }

        $codeNum = (int) str_replace('SB-', '', $lastEmployee->employee_code);
        $nextNum = $codeNum + 1;

        return 'SB-'.str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create employee record and optional linked User login account.
     */
    public function createEmployee(array $data, ?UploadedFile $photoFile = null, bool $createAccount = false, ?string $accountPassword = null, string $accountRole = 'employee'): Employee
    {
        return DB::transaction(function () use ($data, $photoFile, $createAccount, $accountPassword, $accountRole) {
            if (! empty($data['email'])) {
                $data['email'] = strtolower(trim((string) $data['email']));
            }

            if ($photoFile) {
                $filename = Str::uuid().'.'.$photoFile->getClientOriginalExtension();
                $data['profile_photo_path'] = $photoFile->storeAs('profile-photos', $filename, 'public');
            }

            $employee = Employee::create($data);

            if ($createAccount) {
                $password = $accountPassword ?: 'password123';
                $user = User::create([
                    'employee_id' => $employee->id,
                    'outlet_id' => $employee->outlet_id,
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'password' => Hash::make($password),
                    'role' => $accountRole ?: 'employee',
                    'is_active' => $employee->status === 'active',
                ]);

                if ($user->role === 'admin') {
                    $user->assignedOutlets()->sync([$employee->outlet_id]);
                }
            }

            return $employee;
        });
    }

    /**
     * Update employee record and sync linked User login account.
     */
    public function updateEmployee(Employee $employee, array $data, ?UploadedFile $photoFile = null): Employee
    {
        return DB::transaction(function () use ($employee, $data, $photoFile) {
            if (array_key_exists('outlet_id', $data)) {
                if ((int) $data['outlet_id'] !== (int) $employee->outlet_id) {
                    throw ValidationException::withMessages([
                        'outlet_id' => 'Home Outlet tidak dapat diubah melalui Edit Data. Gunakan fitur Pindah Outlet untuk pemindahan permanen.',
                    ]);
                }

                unset($data['outlet_id']);
            }

            if (array_key_exists('email', $data) && $data['email'] !== null) {
                $data['email'] = strtolower(trim((string) $data['email']));
            }

            if ($photoFile) {
                if ($employee->profile_photo_path && Storage::disk('public')->exists($employee->profile_photo_path)) {
                    Storage::disk('public')->delete($employee->profile_photo_path);
                }

                $filename = Str::uuid().'.'.$photoFile->getClientOriginalExtension();
                $data['profile_photo_path'] = $photoFile->storeAs('profile-photos', $filename, 'public');
            }

            $employee->update($data);

            // Sync linked user if exists
            if ($employee->user) {
                $employee->user->update([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'is_active' => $employee->status === 'active',
                ]);
            }

            return $employee;
        });
    }

    /**
     * Reset password for employee's linked User login account.
     */
    public function resetEmployeePassword(Employee $employee, string $newPassword, string $accountRole = 'employee'): User
    {
        $user = $employee->user;

        if (! $user) {
            // Create user account if none exists
            $user = User::create([
                'employee_id' => $employee->id,
                'outlet_id' => $employee->outlet_id,
                'name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'password' => Hash::make($newPassword),
                'role' => $accountRole ?: 'employee',
                'is_active' => $employee->status === 'active',
            ]);
            if ($user->role === 'admin') {
                $user->assignedOutlets()->sync([$employee->outlet_id]);
            }
        } else {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);
        }

        return $user;
    }

    /**
     * Toggle active/inactive status of an employee.
     */
    public function toggleEmployeeStatus(Employee $employee): Employee
    {
        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
        $willBeActive = ($newStatus === 'active');

        if ($employee->user) {
            $this->userRoleService->ensureSuperadminSafety($employee->user, newRole: null, newIsActive: $willBeActive);
        }

        $employee->update(['status' => $newStatus]);

        if ($employee->user) {
            $employee->user->update(['is_active' => $willBeActive]);
        }

        return $employee;
    }

    /**
     * Safely soft-delete an employee, anonymize active PII (email/phone), revoke linked User login, and preserve operational history.
     *
     * @throws ValidationException
     */
    public function deleteEmployee(Employee $employee, User $actor): void
    {
        // 1. Safety check for superadmin user linked to employee
        if ($employee->user) {
            $this->userRoleService->ensureSuperadminSafety($employee->user, newRole: null, newIsActive: false);
        }

        // 2. Active Operation Safety Blockers
        // Blocker A: Active Attendance Session (checked in today, not checked out)
        $activeAttendanceExists = \App\Models\AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->exists();

        if ($activeAttendanceExists) {
            throw ValidationException::withMessages([
                'employee' => ['Karyawan masih memiliki sesi presensi aktif. Lakukan check-out terlebih dahulu sebelum menghapus data karyawan.'],
            ]);
        }

        // Blocker B: Active Overtime Session
        $activeOvertimeExists = \App\Models\OvertimeSession::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhere(function ($subQ) {
                        $subQ->whereNotNull('check_in_at')
                            ->whereNull('check_out_at')
                            ->where('status', '!=', 'cancelled');
                    });
            })
            ->exists();

        if ($activeOvertimeExists) {
            throw ValidationException::withMessages([
                'employee' => ['Karyawan sedang memiliki sesi lembur aktif. Selesaikan sesi lembur terlebih dahulu sebelum menghapus data karyawan.'],
            ]);
        }

        // Blocker C: Pending Shift Swap Request
        $pendingSwapExists = \App\Models\ShiftSwapRequest::where(function ($q) use ($employee) {
            $q->where('requester_employee_id', $employee->id)
                ->orWhere('target_employee_id', $employee->id);
        })
            ->whereIn('status', [\App\Models\ShiftSwapRequest::STATUS_PENDING_TARGET, \App\Models\ShiftSwapRequest::STATUS_PENDING_ADMIN])
            ->exists();

        if ($pendingSwapExists) {
            throw ValidationException::withMessages([
                'employee' => ['Karyawan memiliki permohonan tukar jadwal yang belum selesai. Selesaikan atau batalkan permohonan tukar jadwal terlebih dahulu.'],
            ]);
        }

        // 3. Atomic Transaction: Revoke User login access, anonymize PII, and soft-delete Employee
        DB::transaction(function () use ($employee, $actor) {
            $user = $employee->user;

            if ($user) {
                // Immediately revoke active web sessions for the linked user
                DB::table('sessions')->where('user_id', $user->id)->delete();

                // Deactivate login account and release PII on User (releasing email & phone for future reuse)
                $user->update([
                    'email' => null,
                    'phone' => null,
                    'is_active' => false,
                    'remember_token' => null,
                ]);
            }

            // Anonymize active PII on Employee and set status inactive before soft delete
            $employee->update([
                'email' => null,
                'phone' => null,
                'status' => 'inactive',
            ]);

            // Execute Soft Delete on Employee (preserves ID, employee_code, full_name, outlet_id, timestamps)
            $employee->delete();

            // Record Audit Trail
            \App\Models\AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'employee.deleted',
                'reason' => "Menghapus data karyawan {$employee->full_name} ({$employee->employee_code})",
                'metadata' => [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'outlet_id' => $employee->outlet_id,
                    'user_access_revoked' => $user !== null,
                ],
            ]);
        });
    }

    /**
     * Safely anonymize PII and revoke linked login for a legacy soft-deleted employee.
     *
     * @return array{success: bool, status: string, user_revoked: bool, message?: string}
     */
    public function cleanupLegacyDeletedEmployeePii(Employee $employee): array
    {
        // 1. Must be soft-deleted
        if (! $employee->trashed()) {
            return [
                'success' => false,
                'status' => 'skipped',
                'user_revoked' => false,
                'message' => 'Employee is active (not soft-deleted).',
            ];
        }

        // 2. Must contain at least email or phone to clean
        if (is_null($employee->email) && is_null($employee->phone)) {
            return [
                'success' => false,
                'status' => 'skipped',
                'user_revoked' => false,
                'message' => 'Employee PII is already cleaned.',
            ];
        }

        // 3. Check genuinely linked User via relationship
        $user = $employee->user;

        // 4. Check for Privileged User Conflict
        if ($user && in_array($user->role, ['superadmin', 'owner', 'admin'], true)) {
            return [
                'success' => false,
                'status' => 'conflict',
                'user_revoked' => false,
                'message' => "Linked to privileged User (ID: {$user->id}, Role: {$user->role}). Requires manual review.",
            ];
        }

        // 5. Execute Atomic Cleanup
        try {
            $userRevoked = false;

            DB::transaction(function () use ($employee, $user, &$userRevoked) {
                if ($user) {
                    // Revoke active web sessions
                    DB::table('sessions')->where('user_id', $user->id)->delete();

                    // Revoke user account & release PII
                    $user->update([
                        'email' => null,
                        'phone' => null,
                        'is_active' => false,
                        'remember_token' => null,
                    ]);

                    $userRevoked = true;
                }

                // Clear PII on Employee without altering status, outlet_id, or deleted_at
                Employee::withTrashed()->where('id', $employee->id)->update([
                    'email' => null,
                    'phone' => null,
                ]);

                // Create Audit Trail Log
                \App\Models\AuditLog::create([
                    'user_id' => null,
                    'action' => 'employee.deleted_pii.cleaned',
                    'reason' => "Pembersihan data PII karyawan terdahulu yang telah dihapus (soft-deleted): {$employee->full_name} ({$employee->employee_code})",
                    'metadata' => [
                        'employee_id' => $employee->id,
                        'employee_code' => $employee->employee_code,
                        'source' => 'legacy_backfill',
                        'linked_user_revoked' => $userRevoked,
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'created_at' => now(),
                ]);
            });

            return [
                'success' => true,
                'status' => 'cleaned',
                'user_revoked' => $userRevoked,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'user_revoked' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
