<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

final class DispatchRequestDeptHeadAssignment
{
    public static function requiresChoice(string $tripType): bool
    {
        if ($tripType === 'door_to_door') {
            return false;
        }

        return Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists();
    }

    public static function resolveValidatedId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $id = (int) $raw;
        if ($id <= 0) {
            return null;
        }

        $eligible = User::query()
            ->where('is_active', true)
            ->role('department_head')
            ->whereKey($id)
            ->exists();
        if (! $eligible) {
            abort(422, Messages::REQUEST_INVALID_DEPT_HEAD);
        }

        return $id;
    }
}
