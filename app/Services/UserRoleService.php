<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\RoleChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoleService
{
    /**
     * Check if actor is permitted to manage target user's role.
     */
    public function canActorManageUserRole(User $actor, User $targetUser): bool
    {
        if ($actor->role === UserRole::SUPERADMIN->value) {
            return true;
        }

        if ($actor->role === UserRole::OWNER->value) {
            // Owner cannot manage Superadmin or other Owners
            return ! in_array($targetUser->role, [UserRole::SUPERADMIN->value, UserRole::OWNER->value], true);
        }

        if ($actor->role === UserRole::ADMIN->value) {
            // Admin can only manage Employee role target users
            return $targetUser->role === UserRole::EMPLOYEE->value;
        }

        return false;
    }

    /**
     * Check if actor is permitted to manage target employee's user account.
     */
    public function canActorManageUser(User $actor, ?User $targetUser): bool
    {
        if (! $targetUser) {
            return true;
        }

        return $this->canActorManageUserRole($actor, $targetUser);
    }

    /**
     * Validate role change request and protect against privilege escalation and superadmin lockout.
     */
    public function validateRoleChange(User $actor, User $targetUser, string $newRole, bool $newIsActive = true): void
    {
        // 1. Validate actor management permission
        if (! $this->canActorManageUserRole($actor, $targetUser)) {
            throw new \InvalidArgumentException('Akses ditolak. Anda tidak berwenang mengelola role pengguna ini.');
        }

        // 2. Validate assignable role
        if (! UserRole::canAssign($actor->role, $newRole)) {
            $roleLabel = UserRole::tryFrom($newRole)?->label() ?? $newRole;
            throw new \InvalidArgumentException("Akses ditolak. Anda tidak berwenang menetapkan role {$roleLabel}.");
        }

        // 3. Self-escalation protection
        if ($actor->id === $targetUser->id && $newRole !== $actor->role) {
            throw new \InvalidArgumentException('Akses ditolak. Anda tidak dapat mengubah atau menaikkan role akun sendiri.');
        }

        // 4. Last Active Superadmin Protection
        $this->ensureSuperadminSafety($targetUser, $newRole, $newIsActive);
    }

    /**
     * Ensure at least one active Superadmin remains available in system.
     */
    public function ensureSuperadminSafety(User $targetUser, ?string $newRole = null, bool $newIsActive = true): void
    {
        if ($targetUser->role !== UserRole::SUPERADMIN->value || ! $targetUser->is_active) {
            return;
        }

        $willBeDemoted = ($newRole !== null && $newRole !== UserRole::SUPERADMIN->value);
        $willBeDeactivated = (! $newIsActive);

        if ($willBeDemoted || $willBeDeactivated) {
            $otherActiveSuperadmins = User::where('role', UserRole::SUPERADMIN->value)
                ->where('is_active', true)
                ->where('id', '!=', $targetUser->id)
                ->count();

            if ($otherActiveSuperadmins === 0) {
                throw new \InvalidArgumentException('Minimal satu Superadmin aktif harus tetap tersedia.');
            }
        }
    }

    /**
     * Update target user role with validation, audit logging, session invalidation, and notification.
     */
    public function updateUserRole(User $actor, User $targetUser, string $newRole): User
    {
        $oldRole = $targetUser->role;

        if ($oldRole === $newRole) {
            return $targetUser;
        }

        $this->validateRoleChange($actor, $targetUser, $newRole, $targetUser->is_active);

        DB::transaction(function () use ($actor, $targetUser, $oldRole, $newRole) {
            $targetUser->role = $newRole;
            if ($newRole === 'admin' && ! $targetUser->outlet_id && $targetUser->employee?->outlet_id) {
                $targetUser->outlet_id = $targetUser->employee->outlet_id;
            }
            $targetUser->remember_token = Str::random(60);
            $targetUser->save();

            // Revoke target user's active web sessions
            try {
                DB::table('sessions')->where('user_id', $targetUser->id)->delete();
            } catch (\Throwable $e) {
                // Ignore if sessions table is not using database driver
            }

            // Record Audit Log
            AuditLog::log(
                'user.role_changed',
                $targetUser,
                [
                    'user_id' => $targetUser->id,
                    'user_name' => $targetUser->name,
                    'employee_id' => $targetUser->employee_id,
                    'role' => $oldRole,
                ],
                [
                    'user_id' => $targetUser->id,
                    'user_name' => $targetUser->name,
                    'employee_id' => $targetUser->employee_id,
                    'role' => $newRole,
                ],
                $actor
            );

            // Send notification to target user
            try {
                $targetUser->notify(new RoleChangedNotification($oldRole, $newRole));
            } catch (\Throwable $e) {
                // Ignore notification errors
            }
        });

        return $targetUser;
    }
}
