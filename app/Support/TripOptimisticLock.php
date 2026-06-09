<?php

namespace App\Support;

use App\Models\Trip;
use Illuminate\Support\Carbon;

final class TripOptimisticLock
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function update(Trip $trip, array $attributes, int $expectedVersion): Trip
    {
        $attributes['lock_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = Carbon::now();

        $updated = Trip::query()
            ->whereKey($trip->id)
            ->where('lock_version', $expectedVersion)
            ->update($attributes);

        if ($updated !== 1) {
            abort(409, Messages::OPTIMISTIC_LOCK_CONFLICT);
        }

        return $trip->fresh() ?? $trip->refresh();
    }
}
