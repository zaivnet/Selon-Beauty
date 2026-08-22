<?php

namespace App\Services;

use App\Enums\OutletAccessMode;
use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminOutletAccessService
{
    /** @param array<int, int|string> $outletIds */
    public function update(User $target, string $mode, array $outletIds, User $actor): User
    {
        if ($target->role !== 'admin') {
            return $this->clear($target, $actor);
        }

        $accessMode = OutletAccessMode::tryFrom($mode);
        if (! $accessMode) {
            throw ValidationException::withMessages(['outlet_access_mode' => 'Mode akses outlet tidak valid.']);
        }

        $ids = collect($outletIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($accessMode === OutletAccessMode::ALL) {
            $ids = collect();
        } else {
            $validIds = Outlet::whereIn('id', $ids)->where('is_active', true)->pluck('id');
            if ($validIds->count() !== $ids->count()) {
                throw ValidationException::withMessages(['assigned_outlet_ids' => 'Salah satu outlet yang dipilih tidak aktif atau tidak valid.']);
            }
            $ids = $validIds->map(fn ($id) => (int) $id)->sort()->values();
        }

        return DB::transaction(function () use ($target, $accessMode, $ids, $actor): User {
            $locked = User::with('assignedOutlets')->lockForUpdate()->findOrFail($target->id);
            $beforeMode = $locked->outlet_access_mode ?? OutletAccessMode::SELECTED->value;
            $beforeIds = $locked->assignedOutlets->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $afterIds = $ids->all();

            $primaryOutletId = $accessMode === OutletAccessMode::SELECTED
                ? (in_array((int) $locked->outlet_id, $afterIds, true) ? $locked->outlet_id : ($afterIds[0] ?? null))
                : ($locked->outlet_id && Outlet::whereKey($locked->outlet_id)->where('is_active', true)->exists() ? $locked->outlet_id : null);

            $changed = $beforeMode !== $accessMode->value || $beforeIds !== $afterIds || (int) $locked->outlet_id !== (int) $primaryOutletId;
            if (! $changed) {
                return $locked;
            }

            $locked->forceFill([
                'outlet_access_mode' => $accessMode->value,
                'outlet_id' => $primaryOutletId,
                'remember_token' => Str::random(60),
            ])->save();
            $locked->assignedOutlets()->sync($afterIds);
            $this->revokeSessions($locked);

            AuditLog::log(
                'user.outlet_access_changed',
                $locked,
                ['outlet_access_mode' => $beforeMode, 'assigned_outlet_ids' => $beforeIds],
                ['outlet_access_mode' => $accessMode->value, 'assigned_outlet_ids' => $afterIds],
                $actor,
            );

            return $locked->fresh(['assignedOutlets']);
        });
    }

    public function clear(User $target, User $actor): User
    {
        if (($target->outlet_access_mode ?? OutletAccessMode::SELECTED->value) === OutletAccessMode::SELECTED->value
            && ! $target->assignedOutlets()->exists()) {
            return $target;
        }

        return $this->updateAsSelectedEmpty($target, $actor);
    }

    protected function updateAsSelectedEmpty(User $target, User $actor): User
    {
        return DB::transaction(function () use ($target, $actor): User {
            $target->load('assignedOutlets');
            $before = [
                'outlet_access_mode' => $target->outlet_access_mode,
                'assigned_outlet_ids' => $target->assignedOutlets->pluck('id')->sort()->values()->all(),
            ];
            $target->forceFill(['outlet_access_mode' => 'selected', 'remember_token' => Str::random(60)])->save();
            $target->assignedOutlets()->sync([]);
            $this->revokeSessions($target);
            AuditLog::log('user.outlet_access_changed', $target, $before, [
                'outlet_access_mode' => 'selected', 'assigned_outlet_ids' => [],
            ], $actor);

            return $target->fresh(['assignedOutlets']);
        });
    }

    private function revokeSessions(User $target): void
    {
        try {
            DB::table('sessions')->where('user_id', $target->id)->delete();
        } catch (\Throwable) {
            // Non-database session drivers do not require a sessions table.
        }
    }
}
