<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Services\AdminOutletAccessService;
use App\Services\AttendanceParticipationService;
use App\Services\EmployeeService;
use App\Services\EmployeeTransferService;
use App\Services\OutletScopeService;
use App\Services\UserRoleService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected AttendanceParticipationService $attendanceParticipationService,
        protected AdminOutletAccessService $adminOutletAccessService,
        protected UserRoleService $userRoleService,
        protected OutletScopeService $outletScopeService,
        protected EmployeeTransferService $transferService,
    ) {}

    public function index(Request $request): View
    {
        $this->outletScopeService->ensureAdminHasOutlet($request->user());

        $search = trim($request->input('search', ''));
        $status = $request->input('status', '');

        $query = Employee::with(['jobTitle', 'user', 'outlet']);
        $inputOutletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;
        $query = $this->outletScopeService->scopeByRequestedOutlet($request->user(), $query, $inputOutletId);

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
        $this->outletScopeService->ensureAdminHasOutlet($request->user());

        $suggestedCode = $this->employeeService->generateNextEmployeeCode();
        $jobTitles = JobTitle::where('is_active', true)->orderBy('name')->get();
        $outlets = $this->outletScopeService->getAuthorizedActiveOutlets($request->user());
        $assignableRoles = UserRole::getAssignableRoles($request->user()->role);
        $adminOutlet = ! $this->outletScopeService->isGlobalScope($request->user()) && $outlets->count() === 1
            ? $outlets->first()
            : null;

        return view('admin.employees.create', compact('suggestedCode', 'jobTitles', 'outlets', 'assignableRoles', 'adminOutlet'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $photoFile = $request->file('profile_photo');
        $createUserAccount = $request->boolean('create_user_account');
        $accountPassword = $request->input('account_password');

        $actorRole = $request->user()->role;
        $requestedRole = ($actorRole === UserRole::ADMIN->value)
            ? UserRole::EMPLOYEE->value
            : $request->input('role', UserRole::EMPLOYEE->value);

        if ($actorRole === UserRole::ADMIN->value) {
            $allowedOutletIds = $this->outletScopeService->allowedOutletIds($request->user());
            $requestedOutletId = isset($validated['outlet_id']) ? (int) $validated['outlet_id'] : null;

            if (count($allowedOutletIds) === 1 && $requestedOutletId === null) {
                $requestedOutletId = $allowedOutletIds[0];
            }
            if ($requestedOutletId === null) {
                throw ValidationException::withMessages(['outlet_id' => 'Pilih outlet karyawan dari outlet yang Anda kelola.']);
            }

            $this->outletScopeService->ensureCanAccessOutlet($request->user(), $requestedOutletId);
            $validated['outlet_id'] = $requestedOutletId;
        } elseif (empty($validated['outlet_id'])) {
            $defaultOutlet = Outlet::where('code', 'PUSAT')->first() ?? Outlet::first();
            $validated['outlet_id'] = $defaultOutlet?->id;
        }

        // Security check for role assignment
        if ($createUserAccount && $requestedRole !== UserRole::EMPLOYEE->value) {
            if (! UserRole::canAssign($actorRole, $requestedRole)) {
                throw ValidationException::withMessages(['role' => 'Akses ditolak. Anda tidak berwenang menetapkan role tersebut.']);
            }
        }

        // BACKEND ENFORCEMENT: Role employee/karyawan MUST ALWAYS participate in attendance
        if ($requestedRole === UserRole::EMPLOYEE->value) {
            $validated['attendance_enabled'] = true;
        } else {
            $validated['attendance_enabled'] = $request->has('attendance_enabled')
                ? $request->boolean('attendance_enabled')
                : true;
        }

        $outletAccessMode = $request->input('outlet_access_mode', 'selected');
        $assignedOutletIds = $request->has('outlet_access_mode')
            ? $request->input('assigned_outlet_ids', [])
            : [$validated['outlet_id']];

        unset(
            $validated['profile_photo'],
            $validated['create_user_account'],
            $validated['account_password'],
            $validated['role'],
            $validated['outlet_access_mode'],
            $validated['assigned_outlet_ids'],
        );

        try {
            $employee = DB::transaction(function () use ($validated, $photoFile, $createUserAccount, $accountPassword, $requestedRole, $outletAccessMode, $assignedOutletIds, $request): Employee {
                $employee = $this->employeeService->createEmployee($validated, $photoFile, $createUserAccount, $accountPassword, $requestedRole);

                if ($createUserAccount && $requestedRole === UserRole::ADMIN->value && $employee->user) {
                    $this->adminOutletAccessService->update($employee->user, $outletAccessMode, $assignedOutletIds, $request->user());
                }

                return $employee;
            });
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'unique') || $e->getCode() == 23000) {
                throw ValidationException::withMessages(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
            }
            throw $e;
        }

        return redirect()->route('admin.employees.index')
            ->with('success', "Karyawan {$employee->full_name} ({$employee->employee_code}) berhasil ditambahkan.");
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->outletScopeService->ensureCanManageEmployee($request->user(), $employee);

        $employee->load([
            'jobTitle',
            'user',
            'outlet',
            'outletTransfers.fromOutlet',
            'outletTransfers.toOutlet',
            'outletTransfers.transferredBy',
        ]);

        $canTransfer = $this->outletScopeService->isGlobalScope($request->user());
        $availableOutlets = $canTransfer
            ? Outlet::where('is_active', true)->where('id', '!=', $employee->outlet_id)->orderBy('name')->get()
            : collect();

        return view('admin.employees.show', compact('employee', 'canTransfer', 'availableOutlets'));
    }

    public function edit(Request $request, Employee $employee): View|RedirectResponse
    {
        if (! $this->outletScopeService->canManageEmployee($request->user(), $employee) || ! $this->userRoleService->canActorManageUser($request->user(), $employee->user)) {
            return redirect()->route('admin.employees.index')
                ->with('error', 'Akses ditolak. Anda tidak berwenang mengelola data karyawan ini.');
        }

        $jobTitles = JobTitle::orderBy('name')->get();
        $outlets = $this->outletScopeService->getAuthorizedActiveOutlets($request->user());
        $employee->load(['jobTitle', 'user.assignedOutlets', 'outlet']);
        $assignableRoles = UserRole::getAssignableRoles($request->user()->role);
        $canTransfer = $this->outletScopeService->isGlobalScope($request->user());
        $canResetPassword = $employee->user !== null
            && $this->userRoleService->canActorManageUser($request->user(), $employee->user);
        $adminOutlet = ! $this->outletScopeService->isGlobalScope($request->user()) && $outlets->count() === 1
            ? $outlets->first()
            : null;

        return view('admin.employees.edit', compact('employee', 'jobTitles', 'outlets', 'assignableRoles', 'adminOutlet', 'canTransfer', 'canResetPassword'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        if (! $this->outletScopeService->canManageEmployee($request->user(), $employee) || ! $this->userRoleService->canActorManageUser($request->user(), $employee->user)) {
            throw ValidationException::withMessages(['role' => 'Akses ditolak. Anda tidak berwenang mengelola data karyawan ini.']);
        }

        $validated = $request->validated();
        $photoFile = $request->file('profile_photo');
        $actorRole = $request->user()->role;

        if (array_key_exists('outlet_id', $validated)) {
            if ((int) $validated['outlet_id'] !== (int) $employee->outlet_id) {
                throw ValidationException::withMessages([
                    'outlet_id' => 'Home Outlet tidak dapat diubah melalui Edit Data. Gunakan fitur Pindah Outlet untuk pemindahan permanen.',
                ]);
            }

            // Tolerate legacy clients that still submit the unchanged Home Outlet.
            unset($validated['outlet_id']);
        }

        $requestedRole = ($actorRole === UserRole::ADMIN->value)
            ? UserRole::EMPLOYEE->value
            : $request->input('role');

        $effectiveRole = $requestedRole ?? ($employee->user?->role ?? UserRole::EMPLOYEE->value);

        // BACKEND ENFORCEMENT: Role employee/karyawan MUST ALWAYS participate in attendance
        if ($effectiveRole === UserRole::EMPLOYEE->value) {
            $attendanceEnabled = true;
            $attendanceReason = null;
        } else {
            $attendanceEnabled = array_key_exists('attendance_enabled', $validated)
                ? $request->boolean('attendance_enabled')
                : $employee->participatesInAttendance();
            $attendanceReason = $validated['attendance_participation_reason'] ?? null;
        }

        $outletAccessMode = $request->input('outlet_access_mode');
        $assignedOutletIds = $request->input('assigned_outlet_ids', []);

        if ($effectiveRole === UserRole::ADMIN->value && $outletAccessMode === 'selected') {
            $normalizedIds = collect($assignedOutletIds)->map(fn ($id) => (int) $id)->filter()->unique();
            $activeCount = Outlet::query()->whereIn('id', $normalizedIds)->where('is_active', true)->count();
            if ($activeCount !== $normalizedIds->count()) {
                throw ValidationException::withMessages(['assigned_outlet_ids' => 'Salah satu outlet yang dipilih tidak aktif atau tidak valid.']);
            }
        }

        unset(
            $validated['profile_photo'],
            $validated['role'],
            $validated['attendance_enabled'],
            $validated['attendance_participation_reason'],
            $validated['outlet_access_mode'],
            $validated['assigned_outlet_ids'],
        );

        $actor = $request->user();

        // 1. Update basic employee data
        try {
            DB::transaction(function () use ($employee, $validated, $photoFile, $attendanceEnabled, $attendanceReason, $actor): void {
                $this->employeeService->updateEmployee($employee, $validated, $photoFile);
                $this->attendanceParticipationService->update(
                    $employee,
                    $attendanceEnabled,
                    $attendanceReason,
                    $actor,
                );
            });
            $employee->refresh()->load('user');
        } catch (QueryException $e) {
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

        if ($employee->user) {
            $employee->user->refresh();
            if ($employee->user->role === UserRole::ADMIN->value) {
                $mode = $outletAccessMode ?? ($employee->user->outlet_access_mode ?: 'selected');
                $ids = $outletAccessMode !== null
                    ? $assignedOutletIds
                    : ($employee->user->assignedOutlets()->pluck('outlets.id')->all() ?: [$employee->outlet_id]);
                $this->adminOutletAccessService->update($employee->user, $mode, $ids, $actor);
            } else {
                $this->adminOutletAccessService->clear($employee->user, $actor);
            }
        }

        return redirect()->route('admin.employees.index')
            ->with('success', "Data karyawan {$employee->full_name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Employee $employee): RedirectResponse
    {
        if (! $this->outletScopeService->canManageEmployee($request->user(), $employee) || ! $this->userRoleService->canActorManageUser($request->user(), $employee->user)) {
            return redirect()->back()->with('error', 'Akses ditolak. Anda tidak berwenang mengubah status karyawan ini.');
        }

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
        if (! $this->outletScopeService->canManageEmployee($request->user(), $employee) || ! $this->userRoleService->canActorManageUser($request->user(), $employee->user)) {
            throw ValidationException::withMessages(['role' => 'Akses ditolak. Anda tidak berwenang mengelola akun karyawan ini.']);
        }

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

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        if (! $this->outletScopeService->canManageEmployee($request->user(), $employee) || ! $this->userRoleService->canActorManageUser($request->user(), $employee->user)) {
            return redirect()->back()->with('error', 'Akses ditolak. Anda tidak berwenang menghapus data karyawan ini.');
        }

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

    public function transfer(Request $request, Employee $employee): RedirectResponse
    {
        $this->outletScopeService->ensureCanManageEmployee($request->user(), $employee);

        $validated = $request->validate([
            'destination_outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'destination_outlet_id.required' => 'Pilih outlet tujuan transfer.',
            'destination_outlet_id.exists' => 'Outlet tujuan tidak valid.',
        ]);

        $destinationOutlet = Outlet::findOrFail((int) $validated['destination_outlet_id']);

        $this->transferService->transferOutlet(
            $employee,
            $destinationOutlet,
            $request->user(),
            $validated['notes'] ?? null
        );

        return redirect()->back()
            ->with('success', "Karyawan {$employee->full_name} berhasil dipindahkan ke outlet {$destinationOutlet->name}.");
    }
}
