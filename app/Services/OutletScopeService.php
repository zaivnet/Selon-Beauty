<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OutletScopeService
{
    /**
     * Determine if user has global outlet access scope (Superadmin or Owner).
     */
    public function isGlobalScope(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'owner'], true);
    }

    /**
     * Get the assigned outlet ID for an Admin or User.
     */
    public function getAdminOutletId(User $user): ?int
    {
        return $user->outlet_id ?? $user->employee?->outlet_id;
    }

    /**
     * Check if actor is permitted to manage target employee based on role and outlet scope.
     */
    public function canManageEmployee(User $actor, Employee $employee): bool
    {
        if ($this->isGlobalScope($actor)) {
            return true;
        }

        if ($actor->role === 'admin') {
            $adminOutletId = $this->getAdminOutletId($actor);
            if (! $adminOutletId) {
                return false; // Fail closed if Admin has no assigned outlet
            }

            return (int) $employee->outlet_id === (int) $adminOutletId;
        }

        if ($actor->role === 'employee' && $actor->employee_id && (int) $actor->employee_id === (int) $employee->id) {
            return true;
        }

        return false;
    }

    /**
     * Scope Employee builder query based on authenticated actor's outlet permission.
     */
    public function scopeEmployeesFor(User $actor, Builder $query): Builder
    {
        if ($this->isGlobalScope($actor)) {
            return $query;
        }

        if ($actor->role === 'admin') {
            $adminOutletId = $this->getAdminOutletId($actor);
            if (! $adminOutletId) {
                return $query->whereRaw('1 = 0'); // Fail closed
            }

            return $query->where('employees.outlet_id', $adminOutletId);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Reusable scope for operational models (AttendanceRecord, LeaveRequest, OvertimeRequest, etc.).
     */
    public function scopeQueryFor(User $actor, Builder $query, string $outletColumn = 'outlet_id'): Builder
    {
        if ($this->isGlobalScope($actor)) {
            return $query;
        }

        if ($actor->role === 'admin') {
            $adminOutletId = $this->getAdminOutletId($actor);
            if (! $adminOutletId) {
                return $query->whereRaw('1 = 0'); // Fail closed
            }

            $model = $query->getModel();

            // Direct outlet_id column on model table
            if (in_array($outletColumn, $model->getFillable(), true) || $model->getTable() === 'outlets') {
                return $query->where($model->getTable().'.'.$outletColumn, $adminOutletId);
            }

            // Relationship to employee
            if (method_exists($model, 'employee')) {
                return $query->whereHas('employee', function (Builder $empQuery) use ($adminOutletId) {
                    $empQuery->where('employees.outlet_id', $adminOutletId);
                });
            }

            // Fallback for models without direct outlet or employee relation
            return $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Enforce that actor can manage target employee. Throw 403 AccessDeniedHttpException if unauthorized.
     */
    public function ensureCanManageEmployee(User $actor, ?Employee $employee): void
    {
        if (! $employee || ! $this->canManageEmployee($actor, $employee)) {
            throw new AccessDeniedHttpException('Akses ditolak. Karyawan ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Check if actor can access/manage attendance record.
     */
    public function canManageAttendance(User $actor, ?\App\Models\AttendanceRecord $record): bool
    {
        if (! $record) {
            return false;
        }

        $employee = $record->employee ?? Employee::withTrashed()->find($record->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    /**
     * Enforce authorization for attendance record. Throw 403 if unauthorized.
     */
    public function ensureCanManageAttendance(User $actor, ?\App\Models\AttendanceRecord $record): void
    {
        if (! $this->canManageAttendance($actor, $record)) {
            throw new AccessDeniedHttpException('Akses ditolak. Presensi ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Check if actor can access/manage leave request.
     */
    public function canManageLeave(User $actor, ?\App\Models\LeaveRequest $leave): bool
    {
        if (! $leave) {
            return false;
        }

        $employee = $leave->employee ?? Employee::withTrashed()->find($leave->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    /**
     * Enforce authorization for leave request. Throw 403 if unauthorized.
     */
    public function ensureCanManageLeave(User $actor, ?\App\Models\LeaveRequest $leave): void
    {
        if (! $this->canManageLeave($actor, $leave)) {
            throw new AccessDeniedHttpException('Akses ditolak. Pengajuan izin/cuti ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Check if actor can access/manage overtime request.
     */
    public function canManageOvertime(User $actor, ?\App\Models\OvertimeRequest $overtime): bool
    {
        if (! $overtime) {
            return false;
        }

        $employee = $overtime->employee ?? Employee::withTrashed()->find($overtime->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    /**
     * Enforce authorization for overtime request. Throw 403 if unauthorized.
     */
    public function ensureCanManageOvertime(User $actor, ?\App\Models\OvertimeRequest $overtime): void
    {
        if (! $this->canManageOvertime($actor, $overtime)) {
            throw new AccessDeniedHttpException('Akses ditolak. Pengajuan lembur ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Check if actor can access/manage overtime session.
     */
    public function canManageOvertimeSession(User $actor, ?\App\Models\OvertimeSession $session): bool
    {
        if (! $session) {
            return false;
        }

        $employee = $session->employee ?? Employee::withTrashed()->find($session->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    /**
     * Enforce authorization for overtime session. Throw 403 if unauthorized.
     */
    public function ensureCanManageOvertimeSession(User $actor, ?\App\Models\OvertimeSession $session): void
    {
        if (! $this->canManageOvertimeSession($actor, $session)) {
            throw new AccessDeniedHttpException('Akses ditolak. Sesi lembur ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Check if actor can access/manage shift swap request.
     * For Admin, BOTH requester and target employees must belong to Admin outlet.
     */
    public function canManageShiftSwap(User $actor, ?\App\Models\ShiftSwapRequest $swap): bool
    {
        if (! $swap) {
            return false;
        }

        if ($this->isGlobalScope($actor)) {
            return true;
        }

        if ($actor->role === 'admin') {
            $requester = $swap->requester ?? Employee::withTrashed()->find($swap->requester_employee_id);
            $target = $swap->target ?? Employee::withTrashed()->find($swap->target_employee_id);

            $canReq = $requester ? $this->canManageEmployee($actor, $requester) : false;
            $canTarget = $target ? $this->canManageEmployee($actor, $target) : false;

            return $canReq && $canTarget;
        }

        return false;
    }

    /**
     * Enforce authorization for shift swap request. Throw 403 if unauthorized.
     */
    public function ensureCanManageShiftSwap(User $actor, ?\App\Models\ShiftSwapRequest $swap): void
    {
        if (! $this->canManageShiftSwap($actor, $swap)) {
            throw new AccessDeniedHttpException('Akses ditolak. Permintaan tukar jadwal ini tidak berada di outlet penugasan Anda.');
        }
    }

    /**
     * Get list of active outlets for global actors UI filters.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Outlet>
     */
    public function getActiveOutlets(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Outlet::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Resolve the requested active outlet ID for actor with session persistence and security sanitization.
     *
     * - Superadmin / Owner: May request '0' / null for All Outlets, or valid active outlet ID.
     * - Admin: Strictly limited to assigned outlet ID; browser request input is ignored.
     */
    public function resolveRequestedOutlet(User $actor, ?int $inputOutletId = null): ?int
    {
        if ($this->isGlobalScope($actor)) {
            // Explicit clear request (e.g. 0 or negative) -> All Outlets
            if ($inputOutletId !== null && $inputOutletId <= 0) {
                session()->forget('active_outlet_id');

                return null;
            }

            // Explicit valid outlet ID requested
            if ($inputOutletId !== null && $inputOutletId > 0) {
                $isValidActive = \App\Models\Outlet::where('id', $inputOutletId)
                    ->where('is_active', true)
                    ->exists();

                if ($isValidActive) {
                    session(['active_outlet_id' => $inputOutletId]);

                    return $inputOutletId;
                }

                // Invalid or inactive requested outlet ID -> clear session & fallback to All Outlets safely
                session()->forget('active_outlet_id');

                return null;
            }

            // No explicit input provided -> check session persistence
            $sessionOutletId = session('active_outlet_id');
            if ($sessionOutletId) {
                $isValidActive = \App\Models\Outlet::where('id', $sessionOutletId)
                    ->where('is_active', true)
                    ->exists();

                if ($isValidActive) {
                    return (int) $sessionOutletId;
                }

                session()->forget('active_outlet_id');
            }

            return null;
        }

        // Admin role: Always strictly scoped to assigned outlet ID
        return $this->getAdminOutletId($actor);
    }

    /**
     * Scope Eloquent query by resolved requested outlet ID.
     */
    public function scopeByRequestedOutlet(User $actor, Builder $query, ?int $inputOutletId = null, string $outletColumn = 'outlet_id'): Builder
    {
        $targetOutletId = $this->resolveRequestedOutlet($actor, $inputOutletId);

        if ($targetOutletId !== null) {
            $model = $query->getModel();

            if (in_array($outletColumn, $model->getFillable(), true) || $model->getTable() === 'outlets') {
                return $query->where($model->getTable().'.'.$outletColumn, $targetOutletId);
            }

            if (method_exists($model, 'employee')) {
                return $query->whereHas('employee', function (Builder $empQuery) use ($targetOutletId) {
                    $empQuery->where('employees.outlet_id', $targetOutletId);
                });
            }

            if ($model->getTable() === 'employees') {
                return $query->where('employees.outlet_id', $targetOutletId);
            }

            return $query->whereRaw('1 = 0');
        }

        // Target outlet ID is null (All Outlets for Superadmin/Owner)
        if ($this->isGlobalScope($actor)) {
            return $query;
        }

        // Fail closed for Admin without outlet
        return $query->whereRaw('1 = 0');
    }

    /**
     * Enforce that Admin user has an assigned outlet. Fail closed with 403 if missing.
     */
    public function ensureAdminHasOutlet(User $user): void
    {
        if ($user->role === 'admin' && ! $this->getAdminOutletId($user)) {
            throw new AccessDeniedHttpException('Akun Admin belum memiliki penugasan outlet. Hubungi Owner atau Administrator.');
        }
    }
}
