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
     * Enforce that Admin user has an assigned outlet. Fail closed with 403 if missing.
     */
    public function ensureAdminHasOutlet(User $user): void
    {
        if ($user->role === 'admin' && ! $this->getAdminOutletId($user)) {
            throw new AccessDeniedHttpException('Akun Admin belum memiliki penugasan outlet. Hubungi Owner atau Administrator.');
        }
    }
}
