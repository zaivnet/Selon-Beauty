<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\EmployeeService;
use App\Services\UserRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected UserRoleService $userRoleService
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', '');

        $query = Employee::with(['jobTitle', 'user']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('employee_code', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('full_name')->paginate(10)->withQueryString();

        return view('admin.employees.index', compact('employees', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $suggestedCode = $this->employeeService->generateNextEmployeeCode();
        $jobTitles = JobTitle::where('is_active', true)->orderBy('name')->get();
        $assignableRoles = UserRole::getAssignableRoles($request->user()->role);

        return view('admin.employees.create', compact('suggestedCode', 'jobTitles', 'assignableRoles'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $photoFile = $request->file('profile_photo');
        $createUserAccount = $request->boolean('create_user_account');
        $accountPassword = $request->input('account_password');
        $requestedRole = $request->input('role', UserRole::EMPLOYEE->value);

        // Security check for role assignment
        if ($createUserAccount && $requestedRole !== UserRole::EMPLOYEE->value) {
            if (! UserRole::canAssign($request->user()->role, $requestedRole)) {
                throw ValidationException::withMessages(['role' => 'Akses ditolak. Anda tidak berwenang menetapkan role tersebut.']);
            }
        }

        unset($validated['profile_photo'], $validated['create_user_account'], $validated['account_password'], $validated['role']);

        try {
            $employee = $this->employeeService->createEmployee($validated, $photoFile, $createUserAccount, $accountPassword, $requestedRole);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'unique') || $e->getCode() == 23000) {
                throw ValidationException::withMessages(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
            }
            throw $e;
        }

        return redirect()->route('admin.employees.index')
            ->with('success', "Karyawan {$employee->full_name} ({$employee->employee_code}) berhasil ditambahkan.");
    }

    public function show(Employee $employee): View
    {
        $employee->load(['jobTitle', 'user']);

        return view('admin.employees.show', compact('employee'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        $jobTitles = JobTitle::orderBy('name')->get();
        $employee->load(['jobTitle', 'user']);
        $assignableRoles = UserRole::getAssignableRoles($request->user()->role);

        return view('admin.employees.edit', compact('employee', 'jobTitles', 'assignableRoles'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();
        $photoFile = $request->file('profile_photo');
        $requestedRole = $request->input('role');

        unset($validated['profile_photo'], $validated['role']);

        $actor = $request->user();

        // 1. Update basic employee data
        try {
            $this->employeeService->updateEmployee($employee, $validated, $photoFile);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'unique') || $e->getCode() == 23000) {
                throw ValidationException::withMessages(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
            }
            throw $e;
        }

        // 2. Manage User Role change if requested & user exists
        if ($employee->user && $requestedRole && $requestedRole !== $employee->user->role) {
            try {
                $this->userRoleService->updateUserRole($actor, $employee->user, $requestedRole);
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return redirect()->route('admin.employees.index')
            ->with('success', "Data karyawan {$employee->full_name} berhasil diperbarui.");
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        try {
            $updated = $this->employeeService->toggleEmployeeStatus($employee);
            $statusLabel = $updated->status === 'active' ? 'diaktifkan kembali' : 'dinonaktifkan';

            return redirect()->back()
                ->with('success', "Status karyawan {$updated->full_name} berhasil {$statusLabel}.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function resetPassword(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'in:superadmin,owner,admin,employee'],
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password minimal 6 karakter.',
        ]);

        $requestedRole = $request->input('role', UserRole::EMPLOYEE->value);
        if ($requestedRole !== UserRole::EMPLOYEE->value) {
            if (! UserRole::canAssign($request->user()->role, $requestedRole)) {
                throw ValidationException::withMessages(['role' => 'Akses ditolak. Anda tidak berwenang menetapkan role tersebut.']);
            }
        }

        $this->employeeService->resetEmployeePassword($employee, $request->input('new_password'), $requestedRole);

        return redirect()->back()
            ->with('success', "Password akun login untuk {$employee->full_name} berhasil diperbarui.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->full_name;

        if ($employee->user) {
            try {
                $this->userRoleService->ensureSuperadminSafety($employee->user, newRole: null, newIsActive: false);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            $employee->user->delete();
        }

        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', "Data karyawan {$name} berhasil dihapus (soft delete).");
    }
}
