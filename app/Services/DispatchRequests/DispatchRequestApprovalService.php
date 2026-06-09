<?php

namespace App\Services\DispatchRequests;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\DispatchCargoShipmentProvisioner;
use App\Support\Messages;

class DispatchRequestApprovalService
{
    /**
     * @return array{request: DispatchRequest, trip: Trip}
     */
    public function createTripAfterApproval(
        DispatchRequest $dr,
        User $user,
        string $auditEvent,
        array $beforeSnapshot,
    ): array {
        if (Trip::query()->where('dispatch_request_id', $dr->id)->exists()) {
            abort(409, Messages::TRIP_ALREADY_EXISTS_FOR_REQUEST);
        }

        $dr->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'rejection_reason' => null,
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $user->id,
            'status' => 'approved',
            'depart_at' => $dr->depart_at,
            'arrive_by' => $dr->arrive_by,
            'lock_version' => 0,
        ]);

        DispatchCargoShipmentProvisioner::provision($dr, $trip);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: $auditEvent,
            auditable: $dr,
            before: $beforeSnapshot,
            after: $dr->fresh()->toArray(),
            metadata: ['trip_id' => $trip->id],
        );

        return ['request' => $dr->fresh() ?? $dr, 'trip' => $trip];
    }
}
