<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\AddTripEventRequest;
use App\Http\Requests\Api\Trips\UpdateTripStatusRequest;
use App\Http\Requests\Api\Trips\UpsertTripRecordRequest;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Services\Auditing\AuditLogger;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use App\Support\TripVisibility;
use Illuminate\Support\Facades\DB;

class TripOpsController extends Controller
{
    use ApiResponses;

    public function updateStatus(UpdateTripStatusRequest $request, Trip $trip)
    {
        $data = $request->validated();

        $user = $request->user();

        $beforeStatus = (string) $trip->status;

        $response = DB::transaction(function () use ($trip, $data, $user) {
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

        $newStatus = (string) $data['status'];
        if ($newStatus === 'completed' && $beforeStatus !== 'completed') {
            $trip->refresh();
            app(DispatchRecurringMaintenanceService::class)->consumePackageSessionAfterTripCompletion($trip);
        }

        return $response;
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

    public function upsertRecord(UpsertTripRecordRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $data = $request->validated();
        $user = $request->user();

        $start = $data['start_odometer_km'] ?? null;
        $end = $data['end_odometer_km'] ?? null;
        if ($start !== null && $end !== null && (int) $end < (int) $start) {
            abort(422, 'KM kết thúc phải lớn hơn hoặc bằng KM bắt đầu.');
        }

        $record = $trip->record()->firstOrNew(['trip_id' => $trip->id]);
        if (! $record->exists) {
            $record->created_by = $user->id;
        }
        if (array_key_exists('start_odometer_km', $data) && $data['start_odometer_km'] !== null) {
            $record->start_odometer_km = (int) $data['start_odometer_km'];
        }
        if (array_key_exists('end_odometer_km', $data) && $data['end_odometer_km'] !== null) {
            $record->end_odometer_km = (int) $data['end_odometer_km'];
        }
        if (array_key_exists('driver_notes', $data)) {
            $record->driver_notes = $data['driver_notes'];
        }
        if ($record->start_odometer_km !== null && $record->end_odometer_km !== null) {
            $d = (int) $record->end_odometer_km - (int) $record->start_odometer_km;
            $record->distance_km = max(0, $d);
        }
        $record->save();

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'trip.record.upsert',
            auditable: $trip,
            before: null,
            after: $record->toArray(),
        );

        return $this->ok($record);
    }
}
