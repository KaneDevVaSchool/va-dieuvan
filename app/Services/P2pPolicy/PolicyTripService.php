<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripAudit;
use App\Models\PolicyTripStudent;
use App\Models\StudentPolicy;
use Illuminate\Support\Facades\DB;

class PolicyTripService
{
    public function recordAudit(
        PolicyTrip $trip,
        ?int $userId,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null,
    ): void {
        PolicyTripAudit::create([
            'policy_trip_id' => $trip->id,
            'changed_by' => $userId,
            'changed_at' => now(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    public function refreshTripCounts(PolicyTrip $trip): void
    {
        $trip->refresh();
        $students = PolicyTripStudent::query()
            ->where('policy_trip_id', $trip->id)
            ->where('expected', true)
            ->get();

        $expected = $students->count();
        $boarded = $students->whereNotNull('boarded_at')->count();
        $absent = $students->whereNotNull('absence_reason')->count();

        $trip->update([
            'expected_count' => $expected,
            'boarded_count' => $boarded,
            'absent_count' => $absent,
        ]);
    }

    public function driverHasConflict(int $driverId, string $tripDate, string $timeSlot, ?int $excludeTripId = null): bool
    {
        $q = PolicyTrip::query()
            ->where('driver_id', $driverId)
            ->whereDate('trip_date', $tripDate)
            ->where('time_slot', $timeSlot)
            ->whereNotIn('status', ['cancelled']);

        if ($excludeTripId) {
            $q->where('id', '!=', $excludeTripId);
        }

        return $q->exists();
    }

    public function syncFutureTripsOnPolicySuspend(StudentPolicy $policy, int $changedByUserId): int
    {
        $affected = 0;

        PolicyTripStudent::query()
            ->where('student_policy_id', $policy->id)
            ->where('expected', true)
            ->whereNull('absence_reason')
            ->whereHas('policyTrip', function ($q) {
                $q->whereIn('status', ['scheduled', 'assigned'])
                    ->whereDate('trip_date', '>=', now()->toDateString());
            })
            ->with('policyTrip')
            ->each(function (PolicyTripStudent $pts) use ($changedByUserId, &$affected) {
                $pts->update([
                    'absence_reason' => 'absent_reported',
                    'reported_by' => $changedByUserId,
                ]);
                if ($pts->policyTrip) {
                    $this->refreshTripCounts($pts->policyTrip);
                }
                $affected++;
            });

        return $affected;
    }

    public function tripToListArray(PolicyTrip $trip): array
    {
        $trip->loadMissing(['route', 'driver', 'vehicle']);

        $planned = $trip->planned_departure;
        if ($planned && ! is_string($planned)) {
            $planned = $planned->format('H:i');
        }

        return [
            'id' => $trip->id,
            'trip_date' => $trip->trip_date?->format('Y-m-d'),
            'time_slot' => $trip->time_slot,
            'route_id' => $trip->route_id,
            'route_name' => $trip->route?->name,
            'planned_departure' => $planned,
            'driver_id' => $trip->driver_id,
            'driver_name' => $trip->driver?->full_name,
            'driver_phone' => $trip->driver?->phone,
            'vehicle_id' => $trip->vehicle_id,
            'vehicle_plate' => $trip->vehicle?->license_plate,
            'expected_count' => $trip->expected_count,
            'boarded_count' => $trip->boarded_count,
            'absent_count' => $trip->absent_count,
            'status' => $trip->status,
        ];
    }

    public function studentEntryToArray(PolicyTripStudent $entry): array
    {
        $entry->loadMissing(['student', 'reporter']);

        return [
            'id' => $entry->id,
            'student_id' => $entry->student_id,
            'student_name' => $entry->student?->full_name,
            'class_name' => $entry->student?->grade,
            'expected' => $entry->expected,
            'boarded_at' => $entry->boarded_at?->toIso8601String(),
            'alighted_at' => $entry->alighted_at?->toIso8601String(),
            'absence_reason' => $entry->absence_reason,
            'reported_by_name' => $entry->reporter?->name ?? $entry->reporter?->email,
        ];
    }
}
