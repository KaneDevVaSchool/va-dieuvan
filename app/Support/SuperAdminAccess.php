<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tài khoản bootstrap luôn được coi là superadmin (email cố định + SUPERADMIN_EMAIL).
 */
final class SuperAdminAccess
{
    /**
     * @return list<string> normalized lowercase emails
     */
    public static function emails(): array
    {
        $candidates = array_merge(
            config('permission.bootstrap_superadmin_emails', []),
            array_filter([config('permission.superadmin_email')])
        );

        $normalized = [];
        foreach ($candidates as $email) {
            if (! is_string($email) || $email === '') {
                continue;
            }
            $normalized[] = Str::lower(trim($email));
        }

        return array_values(array_unique($normalized));
    }

    public static function matches(User $user): bool
    {
        $email = $user->email;
        if (! is_string($email) || $email === '') {
            return false;
        }

        return in_array(Str::lower(trim($email)), self::emails(), true);
    }

    /** Gán lại role superadmin nếu email thuộc danh sách bootstrap. */
    public static function ensureRole(User $user): void
    {
        if (! self::matches($user)) {
            return;
        }

        $roleName = (string) config('permission.superadmin_role', 'superadmin');
        $guard = 'web';
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', $guard)
            ->first();

        if (! $role) {
            return;
        }

        $user->syncRoles([$role]);
        $user->forceFill([
            'primary_role_name' => $role->name,
            'primary_role_id' => $role->id,
        ])->save();
    }
}
