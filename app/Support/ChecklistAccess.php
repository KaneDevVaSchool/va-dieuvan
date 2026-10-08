<?php

namespace App\Support;

use App\Models\ChecklistSubmission;
use App\Models\Driver;
use App\Models\User;

final class ChecklistAccess
{
    public static function driverFor(User $user): ?Driver
    {
        return Driver::query()->where('user_id', $user->id)->first();
    }

    public static function userOwns(User $user, ChecklistSubmission $submission): bool
    {
        if ($submission->user_id !== null && (int) $submission->user_id === (int) $user->id) {
            return true;
        }

        $driverId = Driver::query()->where('user_id', $user->id)->value('id');

        return $driverId !== null && (int) $driverId === (int) $submission->driver_id;
    }

    public static function userCanView(User $user, ChecklistSubmission $submission): bool
    {
        return $user->hasPermission('checklist.view') || self::userOwns($user, $submission);
    }
}
