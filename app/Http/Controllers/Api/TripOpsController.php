<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TripOpsController extends Controller
{
    public function updateStatus(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['driver_confirmed', 'in_progress', 'completed', 'incident', 'cancelled'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

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

            return response()->json(['data' => $trip]);
        });
    }

    public function addEvent(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'data' => ['nullable', 'array'],
        ]);

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

        return response()->json(['data' => $event], 201);
    }
}

