<?php

namespace App\Services\Trips;

use App\Models\Driver;
use App\Models\Trip;
use App\Notifications\TripCancelledNotification;
use App\Notifications\TripRescheduledNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TripDriverNotifyService
{
    public function notifyDriversCancelled(Trip $trip): void
    {
        $trip->loadMissing(['dispatchRequest']);

        foreach ($this->resolveDriverUsers($trip) as $driverUser) {
            $driverUser->notify(new TripCancelledNotification(
                tripId: (int) $trip->id,
                tripType: $this->tripType($trip),
                origin: $this->origin($trip),
                destination: $this->destination($trip),
                departAt: $this->departAtIso($trip),
            ));
        }
    }

    public function notifyDriversRescheduled(Trip $trip, Carbon $oldDepart, Carbon $newDepart): void
    {
        $trip->loadMissing(['dispatchRequest']);

        foreach ($this->resolveDriverUsers($trip) as $driverUser) {
            $driverUser->notify(new TripRescheduledNotification(
                tripId: (int) $trip->id,
                tripType: $this->tripType($trip),
                origin: $this->origin($trip),
                destination: $this->destination($trip),
                oldDepartAt: $oldDepart->toIso8601String(),
                newDepartAt: $newDepart->toIso8601String(),
            ));
        }
    }

    /**
     * @return array<int, \App\Models\User>
     */
    public function resolveDriverUsers(Trip $trip): array
    {
        $driverIds = [];

        $assignments = is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [];
        foreach ($assignments as $leg) {
            $id = (int) ($leg['driver_id'] ?? 0);
            if ($id > 0) {
                $driverIds[$id] = true;
            }
        }

        if ($driverIds === [] && (int) ($trip->driver_id ?? 0) > 0) {
            $driverIds[(int) $trip->driver_id] = true;
        }

        $users = [];
        foreach (array_keys($driverIds) as $driverId) {
            $driver = Driver::query()->with('user')->find($driverId);
            $user = $driver?->user;
            if ($user === null) {
                Log::warning('trip_driver_notify.no_user', ['trip_id' => $trip->id, 'driver_id' => $driverId]);
                continue;
            }
            $uid = (int) $user->getKey();
            if (! isset($users[$uid])) {
                $users[$uid] = $user;
            }
        }

        return array_values($users);
    }

    private function tripType(Trip $trip): string
    {
        $t = $trip->dispatchRequest?->trip_type ?? '';

        return is_string($t) && $t !== '' ? $t : 'unspecified';
    }

    private function origin(Trip $trip): string
    {
        return (string) ($trip->dispatchRequest?->origin ?? '');
    }

    private function destination(Trip $trip): string
    {
        return (string) ($trip->dispatchRequest?->destination ?? '');
    }

    private function departAtIso(Trip $trip): string
    {
        $d = $trip->depart_at;
        if ($d === null) {
            return '';
        }

        return ($d instanceof Carbon ? $d : Carbon::parse($d))->toIso8601String();
    }
}
