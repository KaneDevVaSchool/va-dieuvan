<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\AddTripEventRequest;
use App\Http\Requests\Api\Trips\UpdateTripStatusRequest;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;

class TripOpsController extends Controller
{
    use ApiResponses;

    public function updateStatus(UpdateTripStatusRequest $request, Trip $trip)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($trip, $data, $user) {
            $before = $trip->toArray();

            $updates = ['status' => $data['status']];
            if ($data['status'] === 'in_progress') {
                $updates['started_at'] = $trip->started_at ?? now();
            }
            if (in_array($data['status'], ['completed', 'cancelled'], true)) {
                $updates['completed_at'] = $trip->completed_at ?? now();
            }

            $trip->update($updates);

            TripEvent::create([
                'trip_id' => $trip->id,
                'created_by' => $user->id,
                'type' => 'status_change',
                'message' => $data['message'] ?? null,
                'data' => ['from' => $before['status'] ?? null, 'to' => $trip->status],
            ]);

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'trip.status_change',
                auditable: $trip,
                before: $before,
                after: $trip->toArray(),
                metadata: ['message' => $data['message'] ?? null],
            );

            return $this->ok($trip);
        });
    }

    public function addEvent(AddTripEventRequest $request, Trip $trip)
    {
        $data = $request->validated();

        $user = $request->user();

        $event = TripEvent::create([
            'trip_id' => $trip->id,
            'created_by' => $user->id,
            'type' => $data['type'],
            'message' => $data['message'] ?? null,
            'data' => $data['data'] ?? null,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'trip.event.create',
            auditable: $trip,
            before: null,
            after: ['event_id' => $event->id, 'type' => $event->type],
        );

        return $this->created($event);
    }
}
