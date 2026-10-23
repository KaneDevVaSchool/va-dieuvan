<?php

namespace App\Support;

use App\Models\User;
use App\Services\Hr\EmployeeManagerResolver;

final class DispatchRequestDeptHeadAssignment
{
    public static function resolveForRequester(mixed $raw, int $requesterId, string $tripType): ?int
    {
        if ($raw !== null && $raw !== '') {
            return self::resolveValidatedId($raw);
        }
        if (! self::requiresChoice($tripType)) {
            return null;
        }
        $requester = User::find($requesterId);

        return $requester ? app(EmployeeManagerResolver::class)->resolve($requester)?->id : null;
    }

    public static function requiresChoice(string $tripType): bool
    {
        if ($tripType === '' || $tripType === 'door_to_door') {
            return false;
        }

        return true;
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
