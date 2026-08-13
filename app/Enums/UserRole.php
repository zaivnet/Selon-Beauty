<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::EMPLOYEE => 'Karyawan',
        };
    }

    /**
     * Get roles assignable by an actor with the current role.
     *
     * @return array<string, string> Key = role value, Value = UI label
     */
    public static function getAssignableRoles(string $actorRole): array
    {
        if ($actorRole === self::SUPERADMIN->value) {
            return [
                self::EMPLOYEE->value => self::EMPLOYEE->label(),
                self::ADMIN->value => self::ADMIN->label(),
                self::OWNER->value => self::OWNER->label(),
            ];
        }

        if ($actorRole === self::OWNER->value) {
            return [
                self::EMPLOYEE->value => self::EMPLOYEE->label(),
                self::ADMIN->value => self::ADMIN->label(),
            ];
        }

        return [];
    }

    /**
     * Check if actor can assign target role.
     */
    public static function canAssign(string $actorRole, string $targetRole): bool
    {
        $assignable = array_keys(self::getAssignableRoles($actorRole));

        return in_array($targetRole, $assignable, true);
    }
}
