<?php

namespace App\Services\Dispatching;

use App\Models\Driver;
use App\Models\Trip;
use App\Notifications\TripAssignedNotification;
use App\Notifications\TripDriverRemovedNotification;
use App\Services\Auditing\AuditLogger;
use App\Services\Trips\TripDriverNotifyService;
use App\Support\FinancialDataLock;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchingService
{
    public function __construct(
        private readonly TripScheduleLegService $scheduleLegs,
    ) {}

    /**
     * Assign vehicle/driver/provider to a trip (single or per schedule leg).
     */
    public function assignResources(Trip $trip, array $payload): Trip
    {
        return DB::transaction(function () use ($trip, $payload) {
            $trip->refresh();
            FinancialDataLock::assertTripNotPaid($trip);

            $currentStatus = (string) $trip->status;
            if (in_array($currentStatus, ['in_progress', 'completed', 'cancelled', 'incident'], true)) {
                abort(409, Messages::TRIP_ASSIGN_BLOCKED_OPERATIONAL);
            }

            $expectedVersion = (int) ($payload['lock_version'] ?? $trip->lock_version);

            $rawSchedule = $payload['schedule_assignments'] ?? null;
            if (is_array($rawSchedule) && count($rawSchedule) > 0) {
                $assignments = $this->scheduleLegs->normalizeScheduleAssignmentsPayload($trip, $rawSchedule);
            } else {
                $assignments = $this->scheduleLegs->legacySingleAssignmentFromPayload($trip, $payload);
            }

            $trip->loadMissing('dispatchRequest');
            $defsByKey = [];
            foreach ($this->scheduleLegs->buildLegDefinitionsFromSnapshot(
                is_array($trip->dispatchRequest?->wizard_snapshot) ? $trip->dispatchRequest->wizard_snapshot : null,
                (string) ($trip->dispatchRequest?->trip_type ?? ''),
            ) as $def) {
                $defsByKey[$def['key']] = $def;
            }

            $oldByKey = [];
            foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $prev) {
                if (! is_array($prev)) {
                    continue;
                }
                $k = (string) ($prev['key'] ?? '');
                if ($k !== '') {
                    $oldByKey[$k] = $prev;
                }
            }
            foreach ($assignments as $i => $assign) {
                $key = (string) ($assign['key'] ?? '');
                $old = $oldByKey[$key] ?? null;
                if (is_array($old)) {
                    foreach (['status', 'started_at', 'completed_at'] as $field) {
                        if (array_key_exists($field, $old)) {
                            $assignments[$i][$field] = $old[$field];
                        }
                    }
                }
            }

            foreach ($assignments as $assign) {
                $key = (string) ($assign['key'] ?? '');
                $def = $defsByKey[$key] ?? [
                    'depart_at' => $assign['depart_at'] ?? null,
                    'arrive_by' => $assign['arrive_by'] ?? null,
                    'pickup' => '',
                    'dropoff' => '',
                ];
                [$departAt, $arriveBy] = $this->scheduleLegs->resolveLegTimeWindow($trip, $assign, $def);
                $vehicleId = $assign['vehicle_id'] ?? null;
                $driverId = $assign['driver_id'] ?? null;
                $this->assertNoResourceConflict($trip, $departAt, $arriveBy, $vehicleId, $driverId);
            }

            $this->assertNoInternalLegOverlaps($trip, $assignments, $defsByKey);

            $primary = $this->scheduleLegs->primaryTripColumnsFromAssignments($assignments);
            $allAssigned = $this->scheduleLegs->allLegsAssigned($assignments, $trip);
            $newStatus = $allAssigned ? 'assigned' : 'approved';

            $before = $trip->only([
                'vehicle_id',
                'driver_id',
                'transport_provider_id',
                'external_vehicle_ref',
                'external_driver_ref',
                'supplement_transports',
                'schedule_assignments',
                'status',
                'lock_version',
            ]);

            $updatePayload = [
                'vehicle_id' => $primary['vehicle_id'],
                'driver_id' => $primary['driver_id'],
                'transport_provider_id' => $primary['transport_provider_id'],
                'external_vehicle_ref' => $primary['external_vehicle_ref'],
                'external_driver_ref' => $primary['external_driver_ref'],
                'supplement_transports' => $primary['supplement_transports'],
                'schedule_assignments' => $assignments,
                'lock_version' => $expectedVersion + 1,
                'dispatcher_id' => $payload['dispatcher_id'] ?? $trip->dispatcher_id,
                'updated_at' => now(),
            ];

            if (in_array($currentStatus, ['pending', 'approved', 'assigned'], true)) {
                $updatePayload['status'] = $newStatus;
            }

            $updated = Trip::query()
                ->whereKey($trip->id)
                ->where('lock_version', $expectedVersion)
                ->update($updatePayload);

            if ($updated !== 1) {
                abort(409, Messages::OPTIMISTIC_LOCK_CONFLICT);
            }

            $trip->refresh();

            app(AuditLogger::class)->log(
                actorId: $payload['actor_id'] ?? null,
                event: 'trip.assign',
                auditable: $trip,
                before: $before,
                after: $trip->only(array_keys($before)),
                metadata: [
                    'source' => 'DispatchingService',
                    'schedule_assignments' => $assignments,
                ],
            );

            if (empty($payload['suppress_assignment_notifications'])) {
                $oldDriverIds = $this->extractDriverIds(
                    $before['schedule_assignments'] ?? null,
                    $before['driver_id'] ?? null,
                );
                $newDriverIds = $this->extractDriverIds($assignments, $primary['driver_id'] ?? null);
                foreach (array_diff($oldDriverIds, $newDriverIds) as $removedDriverId) {
                    $this->notifyRemovedDriver($trip, $removedDriverId);
                }

                if ($allAssigned) {
                    $this->notifyAssignedDrivers($trip, $assignments, $defsByKey);
                }
            }

            return $trip;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<string, array<string, mixed>>  $defsByKey
     */
    private function assertNoInternalLegOverlaps(Trip $trip, array $assignments, array $defsByKey): void
    {
        $windows = [];
        foreach ($assignments as $assign) {
            $key = (string) ($assign['key'] ?? '');
            $def = $defsByKey[$key] ?? null;
            [$start, $end] = $this->scheduleLegs->resolveLegTimeWindow($trip, $assign, is_array($def) ? $def : null);
            $windows[] = [
                'key' => $key,
                'start' => $start,
                'end' => $end,
                'vehicle_id' => $assign['vehicle_id'] ?? null,
                'driver_id' => $assign['driver_id'] ?? null,
            ];
        }

        $n = count($windows);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $windows[$i];
                $b = $windows[$j];
                if (! $this->intervalsOverlap($a['start'], $a['end'], $b['start'], $b['end'])) {
                    continue;
                }
                if ($a['vehicle_id'] && $b['vehicle_id'] && (int) $a['vehicle_id'] === (int) $b['vehicle_id']) {
                    abort(409, 'Xe bị trùng khung giờ giữa các lịch trong cùng chuyến.');
                }
                if ($a['driver_id'] && $b['driver_id'] && (int) $a['driver_id'] === (int) $b['driver_id']) {
                    abort(409, 'Tài xế bị trùng khung giờ giữa các lịch trong cùng chuyến.');
                }
            }
        }
    }

    private function assertNoResourceConflict(
        Trip $trip,
        Carbon $departAt,
        Carbon $arriveBy,
        mixed $vehicleId,
        mixed $driverId,
    ): void {
        if (! $vehicleId && ! $driverId) {
            return;
        }

        $dbDriver = DB::getDriverName();
        $plannedEndExpr = $dbDriver === 'mysql'
            ? 'COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))'
            : "COALESCE(arrive_by, datetime(depart_at, '+2 hours'))";

        $others = Trip::query()
            ->where('id', '!=', $trip->id)
            ->whereIn('status', ['assigned', 'driver_confirmed', 'in_progress'])
            ->where('depart_at', '<', $arriveBy)
            ->whereRaw("($plannedEndExpr) > ?", [$departAt])
            ->with(['dispatchRequest:id,wizard_snapshot,trip_type'])
            ->get(['id', 'driver_id', 'vehicle_id', 'depart_at', 'arrive_by', 'schedule_assignments', 'dispatch_request_id']);

        foreach ($others as $other) {
            foreach ($this->scheduleLegs->resourceOccupancySlots($other) as $slot) {
                if (! $this->intervalsOverlap($departAt, $arriveBy, $slot['start'], $slot['end'])) {
                    continue;
                }
                $range = $this->formatSlotRange($slot['start'], $slot['end']);
                $code = $this->tripReferenceCode((int) $other->id);
                if ($driverId && $slot['driver_id'] && (int) $slot['driver_id'] === (int) $driverId) {
                    abort(409, sprintf('%s (chuyến %s, %s).', Messages::RESOURCE_DRIVER_OVERLAP, $code, $range));
                }
                if ($vehicleId && $slot['vehicle_id'] && (int) $slot['vehicle_id'] === (int) $vehicleId) {
                    abort(409, sprintf('%s (chuyến %s, %s).', Messages::RESOURCE_VEHICLE_OVERLAP, $code, $range));
                }
            }
        }
    }

    private function tripReferenceCode(int $tripId): string
    {
        return 'TRP-'.str_pad((string) $tripId, 4, '0', STR_PAD_LEFT);
    }

    private function formatSlotRange(Carbon $start, Carbon $end): string
    {
        return $start->format('d/m/Y H:i').'–'.$end->format('H:i');
    }

    private function intervalsOverlap(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lt($bEnd) && $bStart->lt($aEnd);
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<string, array<string, mixed>>  $defsByKey
     */
    private function notifyAssignedDrivers(Trip $trip, array $assignments, array $defsByKey): void
    {
        $trip->loadMissing(['dispatchRequest']);
        $dr = $trip->dispatchRequest;
        $isUrgent = (bool) ($dr?->is_urgent ?? false);
        $tripType = is_string($dr?->trip_type) && $dr->trip_type !== '' ? $dr->trip_type : 'unspecified';

        $notifiedUsers = [];
        foreach ($assignments as $assign) {
            $driverId = (int) ($assign['driver_id'] ?? 0);
            if ($driverId < 1) {
                continue;
            }
            $driver = Driver::query()->with('user')->find($driverId);
            $driverUser = $driver?->user;
            if ($driverUser === null) {
                Log::warning('dispatch.trip_assigned_no_driver_user', [
                    'trip_id' => $trip->id,
                    'driver_id' => $driverId,
                ]);

                continue;
            }
            $uid = (int) $driverUser->getKey();
            if (isset($notifiedUsers[$uid])) {
                continue;
            }
            $notifiedUsers[$uid] = true;

            $key = (string) ($assign['key'] ?? '');
            $def = $defsByKey[$key] ?? [];
            $origin = (string) ($def['pickup'] ?? $dr?->origin ?? '');
            $destination = (string) ($def['dropoff'] ?? $dr?->destination ?? '');
            $departAt = $assign['depart_at'] ?? $def['depart_at'] ?? $trip->depart_at;

            $driverUser->notify(new TripAssignedNotification(
                tripId: $trip->id,
                tripType: $tripType,
                origin: $origin,
                destination: $destination,
                departAt: $departAt instanceof Carbon
                    ? $departAt->toIso8601String()
                    : (string) ($departAt ?? ''),
                isUrgent: $isUrgent,
                scheduleKey: $key !== '' ? $key : null,
            ));
        }
    }

    public function rescheduleDepartAt(Trip $trip, array $payload): Trip
    {
        return DB::transaction(function () use ($trip, $payload) {
            $trip->refresh();
            FinancialDataLock::assertTripNotPaid($trip);

            $expectedVersion = (int) ($payload['lock_version'] ?? $trip->lock_version);

            $oldDepart = $trip->depart_at instanceof Carbon ? $trip->depart_at : Carbon::parse($trip->depart_at);
            $oldArrive = $trip->arrive_by
                ? ($trip->arrive_by instanceof Carbon ? $trip->arrive_by : Carbon::parse($trip->arrive_by))
                : $oldDepart->copy()->addHours(2);

            $newDepart = Carbon::parse($payload['depart_at']);
            $durationSec = max(60, abs($oldDepart->diffInSeconds($oldArrive)));
            $newArrive = $newDepart->copy()->addSeconds($durationSec);

            $this->assertNoResourceConflict($trip, $newDepart, $newArrive, $trip->vehicle_id, $trip->driver_id);

            $before = $trip->only(['depart_at', 'arrive_by', 'lock_version']);

            $updated = Trip::query()
                ->whereKey($trip->id)
                ->where('lock_version', $expectedVersion)
                ->update([
                    'depart_at' => $newDepart,
                    'arrive_by' => $newArrive,
                    'lock_version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                abort(409, Messages::OPTIMISTIC_LOCK_CONFLICT);
            }

            $trip->refresh();

            app(AuditLogger::class)->log(
                actorId: $payload['actor_id'] ?? null,
                event: 'trip.reschedule',
                auditable: $trip,
                before: $before,
                after: $trip->only(array_keys($before)),
                metadata: ['source' => 'DispatchingService'],
            );

            if (empty($payload['suppress_assignment_notifications'])) {
                app(TripDriverNotifyService::class)->notifyDriversRescheduled($trip, $oldDepart, $newDepart);
            }

            return $trip;
        });
    }

    /**
     * @param  list<array<string, mixed>>|null  $scheduleAssignments
     * @return list<int>
     */
    private function extractDriverIds(mixed $scheduleAssignments, mixed $fallbackDriverId): array
    {
        $ids = [];
        if (is_array($scheduleAssignments)) {
            foreach ($scheduleAssignments as $leg) {
                $id = (int) ($leg['driver_id'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }
        if ($ids === [] && (int) ($fallbackDriverId ?? 0) > 0) {
            $ids[(int) $fallbackDriverId] = true;
        }

        return array_keys($ids);
    }

    private function notifyRemovedDriver(Trip $trip, int $driverId): void
    {
        $driver = Driver::query()->with('user')->find($driverId);
        $user = $driver?->user;
        if ($user === null) {
            return;
        }

        $trip->loadMissing(['dispatchRequest']);
        $dr = $trip->dispatchRequest;
        $tripType = is_string($dr?->trip_type) && $dr->trip_type !== '' ? $dr->trip_type : 'unspecified';
        $departAt = $trip->depart_at;

        $user->notify(new TripDriverRemovedNotification(
            tripId: (int) $trip->id,
            tripType: $tripType,
            origin: (string) ($dr?->origin ?? ''),
            destination: (string) ($dr?->destination ?? ''),
            departAt: $departAt instanceof Carbon
                ? $departAt->toIso8601String()
                : (string) ($departAt ?? ''),
        ));
    }
}
