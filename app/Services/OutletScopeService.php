<?php

namespace App\Services;

use App\Enums\OutletAccessMode;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OutletScopeService
{
    /** @var array<int, array<int, int>> */
    private array $resolvedOutletIds = [];

    /** @var array<int, Collection<int, Outlet>> */
    private array $resolvedOutlets = [];

    /**
     * Global role scope is deliberately limited to Owner and Superadmin.
     * Admin `all` expands outlet scope only; it does not expand RBAC privileges.
     */
    public function isGlobalScope(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'owner'], true);
    }

    public function hasAllOutletAccess(User $user): bool
    {
        return $this->isGlobalScope($user)
            || ($user->role === 'admin' && $user->outlet_access_mode === OutletAccessMode::ALL->value);
    }

    /** @return array<int, int> */
    public function allowedOutletIds(User $actor): array
    {
        if (! in_array($actor->role, ['superadmin', 'owner', 'admin'], true)) {
            return [];
        }

        if (isset($this->resolvedOutletIds[$actor->id])) {
            return $this->resolvedOutletIds[$actor->id];
        }

        if ($this->hasAllOutletAccess($actor)) {
            $outlets = Outlet::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $this->resolvedOutlets[$actor->id] = $outlets;

            return $this->resolvedOutletIds[$actor->id] = $outlets->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        if ($actor->role !== 'admin' || $actor->outlet_access_mode !== OutletAccessMode::SELECTED->value) {
            $this->resolvedOutlets[$actor->id] = new Collection;

            return $this->resolvedOutletIds[$actor->id] = [];
        }

        $outlets = $actor->assignedOutlets()
            ->where('is_active', true)
            ->orderBy('outlets.id')
            ->get();
        $this->resolvedOutlets[$actor->id] = $outlets;

        return $this->resolvedOutletIds[$actor->id] = $outlets->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    public function forgetResolvedAccess(?User $actor = null): void
    {
        if ($actor === null) {
            $this->resolvedOutletIds = [];
            $this->resolvedOutlets = [];

            return;
        }

        unset($this->resolvedOutletIds[$actor->id]);
        unset($this->resolvedOutlets[$actor->id]);
    }

    public function canAccessOutlet(User $actor, int|Outlet $outlet): bool
    {
        $outletId = $outlet instanceof Outlet ? (int) $outlet->id : $outlet;

        if ($this->isGlobalScope($actor)) {
            return true;
        }

        return in_array($outletId, $this->allowedOutletIds($actor), true);
    }

    public function ensureCanAccessOutlet(User $actor, int|Outlet $outlet): void
    {
        if (! $this->canAccessOutlet($actor, $outlet)) {
            throw new AccessDeniedHttpException('Akses ditolak. Outlet ini tidak berada dalam penugasan Anda.');
        }
    }

    /** Compatibility/default outlet only. Never use this method as authorization. */
    public function getAdminOutletId(User $user): ?int
    {
        if ($user->role !== 'admin') {
            return $user->outlet_id ?? $user->employee?->outlet_id;
        }

        $allowedIds = $this->allowedOutletIds($user);
        if ($allowedIds === []) {
            return null;
        }

        $primaryId = $user->outlet_id ? (int) $user->outlet_id : null;

        return $primaryId !== null && in_array($primaryId, $allowedIds, true)
            ? $primaryId
            : $allowedIds[0];
    }

    public function canManageEmployee(User $actor, Employee $employee): bool
    {
        if ($this->isGlobalScope($actor)) {
            return true;
        }

        if ($actor->role === 'admin') {
            return in_array((int) $employee->outlet_id, $this->allowedOutletIds($actor), true);
        }

        return $actor->role === 'employee'
            && $actor->employee_id
            && (int) $actor->employee_id === (int) $employee->id;
    }

    public function scopeEmployeesFor(User $actor, Builder $query): Builder
    {
        if ($this->isGlobalScope($actor)) {
            return $query;
        }

        if ($actor->role !== 'admin') {
            return $query->whereRaw('1 = 0');
        }

        $outletIds = $this->allowedOutletIds($actor);

        return $outletIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('employees.outlet_id', $outletIds);
    }

    public function scopeQueryFor(User $actor, Builder $query, string $outletColumn = 'outlet_id'): Builder
    {
        if ($this->isGlobalScope($actor)) {
            return $query;
        }

        if ($actor->role !== 'admin') {
            return $query->whereRaw('1 = 0');
        }

        $outletIds = $this->allowedOutletIds($actor);
        if ($outletIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $this->applyOutletConstraint($query, $outletIds, $outletColumn);
    }

    public function ensureCanManageEmployee(User $actor, ?Employee $employee): void
    {
        if (! $employee || ! $this->canManageEmployee($actor, $employee)) {
            throw new AccessDeniedHttpException('Akses ditolak. Karyawan ini tidak berada di outlet penugasan Anda.');
        }
    }

    public function canManageAttendance(User $actor, ?AttendanceRecord $record): bool
    {
        if (! $record) {
            return false;
        }

        $employee = $record->employee ?? Employee::withTrashed()->find($record->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    public function ensureCanManageAttendance(User $actor, ?AttendanceRecord $record): void
    {
        if (! $this->canManageAttendance($actor, $record)) {
            throw new AccessDeniedHttpException('Akses ditolak. Presensi ini tidak berada di outlet penugasan Anda.');
        }
    }

    public function canManageLeave(User $actor, ?LeaveRequest $leave): bool
    {
        if (! $leave) {
            return false;
        }

        $employee = $leave->employee ?? Employee::withTrashed()->find($leave->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    public function ensureCanManageLeave(User $actor, ?LeaveRequest $leave): void
    {
        if (! $this->canManageLeave($actor, $leave)) {
            throw new AccessDeniedHttpException('Akses ditolak. Pengajuan izin/cuti ini tidak berada di outlet penugasan Anda.');
        }
    }

    public function canManageOvertime(User $actor, ?OvertimeRequest $overtime): bool
    {
        if (! $overtime) {
            return false;
        }

        $employee = $overtime->employee ?? Employee::withTrashed()->find($overtime->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    public function ensureCanManageOvertime(User $actor, ?OvertimeRequest $overtime): void
    {
        if (! $this->canManageOvertime($actor, $overtime)) {
            throw new AccessDeniedHttpException('Akses ditolak. Pengajuan lembur ini tidak berada di outlet penugasan Anda.');
        }
    }

    public function canManageOvertimeSession(User $actor, ?OvertimeSession $session): bool
    {
        if (! $session) {
            return false;
        }

        $employee = $session->employee ?? Employee::withTrashed()->find($session->employee_id);

        return $employee ? $this->canManageEmployee($actor, $employee) : false;
    }

    public function ensureCanManageOvertimeSession(User $actor, ?OvertimeSession $session): void
    {
        if (! $this->canManageOvertimeSession($actor, $session)) {
            throw new AccessDeniedHttpException('Akses ditolak. Sesi lembur ini tidak berada di outlet penugasan Anda.');
        }
    }

    public function canManageShiftSwap(User $actor, ?ShiftSwapRequest $swap): bool
    {
        if (! $swap) {
            return false;
        }

        if ($this->isGlobalScope($actor)) {
            return true;
        }

        if ($actor->role !== 'admin') {
            return false;
        }

        $requester = $swap->requester ?? Employee::withTrashed()->find($swap->requester_employee_id);
        $target = $swap->target ?? Employee::withTrashed()->find($swap->target_employee_id);

        return $requester && $target
            && $this->canManageEmployee($actor, $requester)
            && $this->canManageEmployee($actor, $target);
    }

    public function ensureCanManageShiftSwap(User $actor, ?ShiftSwapRequest $swap): void
    {
        if (! $this->canManageShiftSwap($actor, $swap)) {
            throw new AccessDeniedHttpException('Akses ditolak. Permintaan tukar jadwal ini tidak berada di outlet penugasan Anda.');
        }
    }

    /** @return Collection<int, Outlet> */
    public function getActiveOutlets(): Collection
    {
        return Outlet::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, Outlet> */
    public function getAuthorizedActiveOutlets(User $actor): Collection
    {
        if ($this->isGlobalScope($actor)) {
            return $this->getActiveOutlets();
        }

        $this->allowedOutletIds($actor);

        return ($this->resolvedOutlets[$actor->id] ?? new Collection)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** Resolve one operational context. Null represents all accessible outlets in scope. */
    public function resolveRequestedOutlet(User $actor, ?int $inputOutletId = null): ?int
    {
        $outletModeService = app(OutletModeService::class);
        if ($outletModeService->isSingleOutlet()) {
            $single = $outletModeService->getSingleOperationalOutlet();

            return $single?->id;
        }

        if ($this->isGlobalScope($actor)) {
            return $this->resolveGlobalOutletContext($actor, $inputOutletId);
        }

        if ($actor->role !== 'admin') {
            $this->forgetOutletContext();

            return null;
        }

        $allowedIds = $this->allowedOutletIds($actor);
        if ($allowedIds === []) {
            $this->forgetOutletContext();

            return null;
        }

        // If admin has only 1 assigned outlet, always return that outlet
        if (count($allowedIds) === 1) {
            $this->rememberOutletContext($actor, $allowedIds[0]);

            return $allowedIds[0];
        }

        // Admin with > 1 outlets:
        // 1. Explicit single outlet request
        if ($inputOutletId !== null && $inputOutletId > 0) {
            if (in_array($inputOutletId, $allowedIds, true)) {
                $this->rememberOutletContext($actor, $inputOutletId);

                return $inputOutletId;
            }

            // Sanitize invalid/tampered input to primary assigned outlet
            $fallbackId = $this->getAdminOutletId($actor) ?? $allowedIds[0];
            $this->rememberOutletContext($actor, $fallbackId);

            return $fallbackId;
        }

        // 2. Explicit "Semua Outlet" request (inputOutletId === 0 or <= 0)
        if ($inputOutletId !== null && $inputOutletId <= 0) {
            $this->forgetOutletContext();

            return null;
        }

        // 3. No query parameter provided (inputOutletId === null)
        $sessionOutletId = (int) session('active_outlet_user_id') === (int) $actor->id
            ? (int) session('active_outlet_id', 0)
            : 0;
        if ($sessionOutletId > 0 && in_array($sessionOutletId, $allowedIds, true)) {
            return $sessionOutletId;
        }

        return null; // Default for >1 outlets is Semua Outlet (null)
    }

    public function scopeByRequestedOutlet(User $actor, Builder $query, ?int $inputOutletId = null, string $outletColumn = 'outlet_id'): Builder
    {
        $targetOutletId = $this->resolveRequestedOutlet($actor, $inputOutletId);

        if ($targetOutletId !== null) {
            return $this->applyOutletConstraint($query, [$targetOutletId], $outletColumn);
        }

        return $this->isGlobalScope($actor) ? $query : $this->scopeQueryFor($actor, $query, $outletColumn);
    }

    public function ensureAdminHasOutlet(User $user): void
    {
        if ($user->role === 'admin' && $this->allowedOutletIds($user) === []) {
            throw new AccessDeniedHttpException('Akun Admin belum memiliki penugasan outlet. Hubungi Owner atau Administrator.');
        }
    }

    /** @param array<int, string> $globalRoles */
    public function scopeNotificationRecipientsForOutlet(Builder $query, int $outletId, array $globalRoles): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $roleQuery) use ($outletId, $globalRoles) {
                $roleQuery->whereIn('role', $globalRoles)
                    ->orWhere(function (Builder $adminQuery) use ($outletId) {
                        $adminQuery->where('role', 'admin')
                            ->where(function (Builder $accessQuery) use ($outletId) {
                                $accessQuery->where('outlet_access_mode', OutletAccessMode::ALL->value)
                                    ->orWhere(function (Builder $selectedQuery) use ($outletId) {
                                        $selectedQuery->where('outlet_access_mode', OutletAccessMode::SELECTED->value)
                                            ->whereHas('assignedOutlets', fn (Builder $outletQuery) => $outletQuery
                                                ->where('outlets.id', $outletId)
                                                ->where('outlets.is_active', true));
                                    });
                            });
                    });
            });
    }

    /** @param array<int, int> $outletIds */
    private function applyOutletConstraint(Builder $query, array $outletIds, string $outletColumn): Builder
    {
        $model = $query->getModel();

        if ($model->getTable() === 'employees') {
            return $query->whereIn('employees.outlet_id', $outletIds);
        }

        if (in_array($outletColumn, $model->getFillable(), true) || $model->getTable() === 'outlets') {
            return $query->whereIn($model->getTable().'.'.$outletColumn, $outletIds);
        }

        if (method_exists($model, 'employee')) {
            return $query->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery
                ->whereIn('employees.outlet_id', $outletIds));
        }

        return $query->whereRaw('1 = 0');
    }

    private function resolveGlobalOutletContext(User $actor, ?int $inputOutletId): ?int
    {
        if ($inputOutletId !== null && $inputOutletId <= 0) {
            $this->forgetOutletContext();

            return null;
        }

        if ($inputOutletId !== null) {
            if (Outlet::query()->whereKey($inputOutletId)->where('is_active', true)->exists()) {
                $this->rememberOutletContext($actor, $inputOutletId);

                return $inputOutletId;
            }

            $this->forgetOutletContext();

            return null;
        }

        $sessionOutletId = (int) session('active_outlet_user_id') === (int) $actor->id
            ? (int) session('active_outlet_id', 0)
            : 0;
        if ($sessionOutletId > 0 && Outlet::query()->whereKey($sessionOutletId)->where('is_active', true)->exists()) {
            return $sessionOutletId;
        }

        return null; // Default for global scope is Semua Outlet (null)
    }

    private function rememberOutletContext(User $actor, int $outletId): void
    {
        session(['active_outlet_id' => $outletId, 'active_outlet_user_id' => $actor->id]);
    }

    private function forgetOutletContext(): void
    {
        session()->forget(['active_outlet_id', 'active_outlet_user_id']);
    }
}
