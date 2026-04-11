<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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

        return false;
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
            }
        });
    }
}
