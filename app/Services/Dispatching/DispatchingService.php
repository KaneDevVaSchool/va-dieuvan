<?php

namespace App\Services\Dispatching;

use App\Models\Trip;
use App\Notifications\TripAssignedNotification;
use App\Services\Auditing\AuditLogger;
use App\Support\FinancialDataLock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchingService
{
    /**
     * Placeholder service for assigning vehicle/driver/provider to a trip.
     * Business rules (BR-002 optimistic locking, conflict checks) are enforced here.
     */
    public function assignResources(Trip $trip, array $payload): Trip
    {
        return DB::transaction(function () use ($trip, $payload) {
            $trip->refresh();
            FinancialDataLock::assertTripNotPaid($trip);

            $expectedVersion = (int) ($payload['lock_version'] ?? $trip->lock_version);

            $departAt = $trip->depart_at instanceof Carbon ? $trip->depart_at : Carbon::parse($trip->depart_at);
            $arriveBy = $trip->arrive_by
                ? ($trip->arrive_by instanceof Carbon ? $trip->arrive_by : Carbon::parse($trip->arrive_by))
                : $departAt->copy()->addHours(2);

            $vehicleId = $payload['vehicle_id'] ?? null;
            $driverId = $payload['driver_id'] ?? null;
            $providerId = $payload['transport_provider_id'] ?? null;

            // BR-002: chống trùng lịch (interval overlap: a.start < b.end AND b.start < a.end)
            // Nếu trip.arrive_by null, dùng depart_at + 2h làm planned end.
            $dbDriver = DB::getDriverName();
            $plannedEndExpr = $dbDriver === 'mysql'
                ? 'COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))'
                : "COALESCE(arrive_by, datetime(depart_at, '+2 hours'))";

            $conflictBase = Trip::query()
                ->where('id', '!=', $trip->id)
                ->whereIn('status', ['assigned', 'driver_confirmed', 'in_progress'])
                ->where('depart_at', '<', $arriveBy)
                ->whereRaw("($plannedEndExpr) > ?", [$departAt]);

            if ($vehicleId) {
                if ((clone $conflictBase)->where('vehicle_id', $vehicleId)->exists()) {
                    abort(409, 'Xe bị trùng lịch trong khung giờ này.');
                }
            }

            if ($driverId) {
                if ((clone $conflictBase)->where('driver_id', $driverId)->exists()) {
                    abort(409, 'Tài xế bị trùng lịch trong khung giờ này.');
                }
            }

            $supplementRaw = $payload['supplement_transports'] ?? null;
            $supplementJson = null;
            if (is_array($supplementRaw)) {
                $taxis   = array_values(array_filter($supplementRaw['taxis']   ?? [], 'is_array'));
                $vendors = array_values(array_filter($supplementRaw['vendors'] ?? [], 'is_array'));
                if (count($taxis) > 0 || count($vendors) > 0) {
                    $sanitize = fn (array $item): array => array_filter([
                        'id'                 => (string) ($item['id'] ?? ''),
                        'label'              => substr((string) ($item['label'] ?? ''), 0, 255),
                        'supplementSeats'    => isset($item['supplementSeats']) ? (int) $item['supplementSeats'] : null,
                        'isCustom'           => isset($item['isCustom']) ? (bool) $item['isCustom'] : null,
                        'externalVehicleRef' => isset($item['externalVehicleRef']) ? substr((string) $item['externalVehicleRef'], 0, 255) : null,
                        'externalDriverRef'  => isset($item['externalDriverRef'])  ? substr((string) $item['externalDriverRef'],  0, 255) : null,
                        'contactNotes'       => isset($item['contactNotes']) ? substr((string) $item['contactNotes'], 0, 500) : null,
                    ], fn ($v) => $v !== null && $v !== '');
                    $supplementJson = json_encode([
                        'taxis'   => array_map($sanitize, $taxis),
                        'vendors' => array_map($sanitize, $vendors),
                    ]);
                }
            }

            $before = $trip->only([
                'vehicle_id',
                'driver_id',
                'transport_provider_id',
                'external_vehicle_ref',
                'external_driver_ref',
                'supplement_transports',
                'status',
                'lock_version',
            ]);

            $updated = Trip::query()
                ->whereKey($trip->id)
                ->where('lock_version', $expectedVersion)
                ->update([
                    'vehicle_id'             => $vehicleId,
                    'driver_id'              => $driverId,
                    'transport_provider_id'  => $providerId,
                    'external_vehicle_ref'   => $payload['external_vehicle_ref'] ?? null,
                    'external_driver_ref'    => $payload['external_driver_ref'] ?? null,
                    'supplement_transports'  => $supplementJson,
                    'status'                 => 'assigned',
                    'lock_version'           => $expectedVersion + 1,
                    'dispatcher_id'          => $payload['dispatcher_id'] ?? $trip->dispatcher_id,
                    'updated_at'             => now(),
                ]);

            if ($updated !== 1) {
                abort(409, 'Dữ liệu đã thay đổi, vui lòng tải lại (optimistic lock).');
            }

            $trip->refresh();

            app(AuditLogger::class)->log(
                actorId: $payload['actor_id'] ?? null,
                event: 'trip.assign',
                auditable: $trip,
                before: $before,
                after: $trip->only(array_keys($before)),
                metadata: [
                    'source'               => 'DispatchingService',
                    'supplement_transports' => $supplementJson ? json_decode($supplementJson, true) : null,
                ],
            );

            $trip->loadMissing(['driver.user', 'dispatchRequest']);
            $driverUser = $trip->driver?->user;
            $debugLog = config('dispatch.debug_notification_log');
            $queueConnection = config('queue.default');
            if ($driverUser !== null) {
                $dr = $trip->dispatchRequest;
                $isUrgent = (bool) ($dr?->is_urgent ?? false);
                $queueName = $isUrgent
                    ? config('dispatch.notifications_queue_urgent')
                    : config('dispatch.notifications_queue_default');

                $driverUser->notify(new TripAssignedNotification(
                    tripId: $trip->id,
                    tripType: is_string($dr?->trip_type) && $dr->trip_type !== ''
                        ? $dr->trip_type
                        : 'unspecified',
                    origin: (string) ($dr?->origin ?? ''),
                    destination: (string) ($dr?->destination ?? ''),
                    departAt: $trip->depart_at instanceof Carbon
                        ? $trip->depart_at->toIso8601String()
                        : (string) ($trip->depart_at ?? ''),
                    isUrgent: $isUrgent,
                ));

                if ($debugLog) {
                    Log::info('dispatch.trip_assigned_notification_queued', [
                        'trip_id' => $trip->id,
                        'driver_id' => $trip->driver_id,
                        'driver_user_id' => $driverUser->getKey(),
                        'queue_connection' => $queueConnection,
                        'notification_queue' => $queueName,
                        'urgent' => $isUrgent,
                    ]);
                }
            } elseif ($trip->driver_id !== null) {
                Log::warning('dispatch.trip_assigned_no_driver_user', [
                    'trip_id' => $trip->id,
                    'driver_id' => $trip->driver_id,
                    'hint' => 'Tài xế chưa có user liên kết — bỏ TripAssignedNotification.',
                ]);
            }

            return $trip;
        });
    }

    /**
     * Đổi khung giờ chuyến (kéo trên timeline) — giữ độ dài [depart, arrive] và kiểm tra trùng xe/tài xế.
     */
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

            $dbDriver = DB::getDriverName();
            $plannedEndExpr = $dbDriver === 'mysql'
                ? 'COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))'
                : "COALESCE(arrive_by, datetime(depart_at, '+2 hours'))";

            $conflictBase = Trip::query()
                ->where('id', '!=', $trip->id)
                ->whereIn('status', ['assigned', 'driver_confirmed', 'in_progress'])
                ->where('depart_at', '<', $newArrive)
                ->whereRaw("($plannedEndExpr) > ?", [$newDepart]);

            if ($trip->vehicle_id && (clone $conflictBase)->where('vehicle_id', $trip->vehicle_id)->exists()) {
                abort(409, 'Xe bị trùng lịch trong khung giờ này.');
            }

            if ($trip->driver_id && (clone $conflictBase)->where('driver_id', $trip->driver_id)->exists()) {
                abort(409, 'Tài xế bị trùng lịch trong khung giờ này.');
            }

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
                abort(409, 'Dữ liệu đã thay đổi, vui lòng tải lại (optimistic lock).');
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

            return $trip;
        });
    }
}
