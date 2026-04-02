<?php

namespace App\Services\Dispatching;

use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DispatchingService
{
    /**
     * Placeholder service for assigning vehicle/driver/provider to a trip.
     * Business rules (BR-002 optimistic locking, conflict checks) are enforced here.
     */
    public function assignResources(Trip $trip, array $payload): Trip
    {
        return DB::transaction(function () use ($trip, $payload) {
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
                ? "COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))"
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

            $before = $trip->only([
                'vehicle_id',
                'driver_id',
                'transport_provider_id',
                'external_vehicle_ref',
                'external_driver_ref',
                'status',
                'lock_version',
            ]);

            $updated = Trip::query()
                ->whereKey($trip->id)
                ->where('lock_version', $expectedVersion)
                ->update([
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driverId,
                    'transport_provider_id' => $providerId,
                    'external_vehicle_ref' => $payload['external_vehicle_ref'] ?? null,
                    'external_driver_ref' => $payload['external_driver_ref'] ?? null,
                    'status' => 'assigned',
                    'lock_version' => $expectedVersion + 1,
                    'dispatcher_id' => $payload['dispatcher_id'] ?? $trip->dispatcher_id,
                    'updated_at' => now(),
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
                metadata: ['source' => 'DispatchingService'],
            );

            return $trip;
        });
    }
}

