<?php

namespace App\Support;

use App\Models\TripCost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class TripCostAccess
{
    public static function userCanView(User $user, TripCost $cost): bool
    {
        if ($user->hasPermission('trip.view_all')) {
            return true;
        }

        $cost->loadMissing('trip');

        if ($cost->trip_id === null) {
            return (int) $cost->created_by === (int) $user->id
                || $user->hasPermission('trip.cost.reconcile');
        }

        return $cost->trip !== null && TripVisibility::userCanViewTrip($user, $cost->trip);
    }

    public static function applyVisibleToUserScope(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('trip.view_all')) {
            return $query;
        }

        $tripIds = TripVisibility::visibleTripsQuery($user)->pluck('id');

        return $query->where(function (Builder $b) use ($user, $tripIds) {
            if ($tripIds->isNotEmpty()) {
                $b->whereIn('trip_id', $tripIds);
            }
            $b->orWhere(function (Builder $inner) use ($user) {
                $inner->whereNull('trip_id')->where('created_by', $user->id);
            });
        });
    }

    public static function driverCanMutateCostRow(User $user, TripCost $cost): bool
    {
        if ((int) $cost->created_by !== (int) $user->id) {
            return false;
        }

        if (! in_array($cost->status, ['draft', 'submitted'], true)) {
            return false;
        }

        return FinancialDataLock::tripCostAllowsDriverEdits($cost);
    }
}
