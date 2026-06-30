<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TripVisibility
{
    public static function userCanViewTrip(User $user, Trip $trip): bool
    {
        if ($user->hasPermission('trip.view_all')) {
            return true;
        }

        if (! $user->hasPermission('trip.view_own')) {
            return false;
        }

        $driverId = Driver::query()->where('user_id', $user->id)->value('id');

        if ((int) $trip->dispatcher_id === (int) $user->id) {
            return true;
        }

        if ($driverId && (int) $trip->driver_id === (int) $driverId) {
            return true;
        }

        if ($driverId && self::tripHasDriverOnScheduleLegs($trip, (int) $driverId)) {
            return true;
        }

        return $trip->dispatchRequest()
            ->withTrashed()
            ->where('requester_id', $user->id)
            ->exists();
    }

    /**
     * @return Builder<Trip>
     */
    public static function visibleTripsQuery(User $user): Builder
    {
        $q = Trip::query();

        if ($user->hasPermission('trip.view_all')) {
            return $q;
        }

        if (! $user->hasPermission('trip.view_own')) {
            return $q->whereRaw('1 = 0');
        }

        $driverId = Driver::query()->where('user_id', $user->id)->value('id');

        return $q->where(function (Builder $w) use ($user, $driverId) {
            $w->where('dispatcher_id', $user->id);
            if ($driverId) {
                $w->orWhere('driver_id', $driverId);
                $w->orWhere(fn (Builder $legQ) => self::applyScheduleLegDriverFilter($legQ, (int) $driverId));
            }
            $w->orWhereHas('dispatchRequest', fn (Builder $dr) => $dr->withTrashed()->where('requester_id', $user->id));
        });
    }

    public static function tripHasDriverOnScheduleLegs(Trip $trip, int $driverId): bool
    {
        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $leg) {
            if (is_array($leg) && (int) ($leg['driver_id'] ?? 0) === $driverId) {
                return true;
            }
        }

        return false;
    }

    private static function applyScheduleLegDriverFilter(Builder $q, int $driverId): void
    {
        $q->whereNotNull('schedule_assignments');
        if (DB::getDriverName() === 'mysql') {
            $q->whereRaw(
                'JSON_SEARCH(schedule_assignments, \'one\', ?, NULL, \'$[*].driver_id\') IS NOT NULL',
                [(string) $driverId]
            );

            return;
        }

        $needle = '"driver_id":'.$driverId;
        $q->where(function (Builder $w) use ($needle) {
            $w->where('schedule_assignments', 'like', '%'.$needle.',%')
                ->orWhere('schedule_assignments', 'like', '%'.$needle.'}%');
        });
    }
}
