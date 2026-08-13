<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeService
{
    public function __construct(protected ?UserRoleService $userRoleService = null)
    {
        $this->userRoleService = $userRoleService ?? new UserRoleService();
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
                User::create([
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'password' => Hash::make($password),
                    'role' => $accountRole ?: 'employee',
                    'is_active' => $employee->status === 'active',
                ]);
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
                'name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'password' => Hash::make($newPassword),
                'role' => $accountRole ?: 'employee',
                'is_active' => $employee->status === 'active',
            ]);
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
}
