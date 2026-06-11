<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DriverAssignableVehicles
{
    /**
     * @return Collection<int, Vehicle>
     */
    public static function vehiclesForUser(User $user): Collection
    {
        if ($user->hasPermission('trip.cost.reconcile') || $user->hasPermission('trip.view_all')) {
            return Vehicle::query()
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'type', 'seat_count', 'status']);
        }

        $driverId = Driver::query()->where('user_id', $user->id)->value('id');
        $driverId = $driverId !== null ? (int) $driverId : null;

        $ids = collect();

        if ($driverId !== null) {
            $ids = $ids->merge(
                Vehicle::query()
                    ->where('default_driver_id', $driverId)
                    ->pluck('id'),
            );
        }

        $tripVehicleIds = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->whereNotNull('vehicle_id')
            ->distinct()
            ->pluck('vehicle_id');

        $ids = $ids->merge($tripVehicleIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Vehicle::query()
            ->whereIn('id', $ids)
            ->orderBy('license_plate')
            ->get(['id', 'license_plate', 'type', 'seat_count', 'status']);
    }

    public static function userCanAssign(User $user, int $vehicleId): bool
    {
        if ($vehicleId <= 0) {
            return false;
        }

        if ($user->hasPermission('trip.cost.reconcile') || $user->hasPermission('trip.view_all')) {
            return Vehicle::query()->whereKey($vehicleId)->exists();
        }

        return self::vehiclesForUser($user)->contains(fn (Vehicle $v) => (int) $v->id === $vehicleId);
    }

    /**
     * @param  Builder<Vehicle>  $query
     */
    public static function applyVisibleToUserScope(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('trip.cost.reconcile') || $user->hasPermission('trip.view_all')) {
            return $query;
        }

        $allowed = self::vehiclesForUser($user)->pluck('id');

        return $query->whereIn('id', $allowed);
    }
}
