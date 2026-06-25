<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\AddTripEventRequest;
use App\Http\Requests\Api\Trips\DeleteTripEventRequest;
use App\Http\Requests\Api\Trips\UpdateTripStatusRequest;
use App\Http\Requests\Api\Trips\UpsertTripRecordRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Services\Auditing\AuditLogger;
use App\Services\Costs\TripWizardCostProvisioner;
use App\Services\Dispatching\TripScheduleLegService;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use App\Services\RecurringDispatch\RecurringBudgetAlertService;
use App\Services\Trips\TripDriverNotifyService;
use App\Services\Trips\TripStatusTransitionValidator;
use App\Support\Messages;
use App\Support\TripOptimisticLock;
use App\Support\TripVisibility;
use Illuminate\Support\Facades\DB;

class TripOpsController extends Controller
{
    use ApiResponses;

    public function updateStatus(UpdateTripStatusRequest $request, Trip $trip)
    {
        $data = $request->validated();

        $user = $request->user();

        $scheduleLegs = app(TripScheduleLegService::class);
        $scheduleKey = isset($data['schedule_key']) ? trim((string) $data['schedule_key']) : null;
        if ($scheduleKey === '') {
            $scheduleKey = null;
        }

        if ($user->hasRole('driver') && $scheduleKey === null) {
            $trip->loadMissing('dispatchRequest');
            $defs = $scheduleLegs->buildLegDefinitionsFromSnapshot(
                is_array($trip->dispatchRequest?->wizard_snapshot) ? $trip->dispatchRequest->wizard_snapshot : null,
                (string) ($trip->dispatchRequest?->trip_type ?? ''),
            );
            if (count($defs) > 1) {
                abort(422, 'Chuyến có nhiều lịch trình — gửi schedule_key khi đổi trạng thái.');
            }
        }

        if ($scheduleKey !== null && $user->hasRole('driver')) {
            $driverId = (int) (Driver::query()->where('user_id', $user->id)->value('id') ?? 0);
            abort_unless(
                $driverId > 0 && $scheduleLegs->driverAssignedToLeg($trip, $driverId, $scheduleKey),
                403,
            );
        }

        $newStatus = (string) $data['status'];
        $beforeStatus = (string) $trip->status;

        $response = DB::transaction(function () use ($trip, $data, $user, $scheduleLegs, $scheduleKey, $newStatus) {
            /** @var Trip $locked */
            $locked = Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $expectedVersion = array_key_exists('lock_version', $data)
                ? (int) $data['lock_version']
                : (int) $locked->lock_version;

            if ((int) $locked->lock_version !== $expectedVersion) {
                abort(409, Messages::OPTIMISTIC_LOCK_CONFLICT);
            }

            $fromStatus = (string) $locked->status;
            if ($scheduleKey !== null) {
                $legFrom = $scheduleLegs->resolveLegStatusForTransition($locked, $scheduleKey);
                if ($legFrom !== null) {
                    $fromStatus = $legFrom;
                }
            }

            app(TripStatusTransitionValidator::class)->assertCanTransition(
                $fromStatus,
                $newStatus,
            );

            $before = $locked->toArray();

            $applied = $scheduleLegs->applyStatusChange($locked, $newStatus, $scheduleKey);
            $updates = $applied['trip'];
            if ($applied['schedule_assignments'] !== null) {
                $updates['schedule_assignments'] = $applied['schedule_assignments'];
            }

            $fresh = TripOptimisticLock::update($locked, $updates, $expectedVersion);

            $eventData = [
                'from' => $before['status'] ?? null,
                'to' => $fresh->status,
            ];
            if ($scheduleKey !== null) {
                $eventData['schedule_key'] = $scheduleKey;
                $eventData['leg_status'] = $newStatus;
            }

            TripEvent::create([
                'trip_id' => $fresh->id,
                'created_by' => $user->id,
                'type' => 'status_change',
                'message' => $data['message'] ?? null,
                'data' => $eventData,
            ]);

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'trip.status_change',
                auditable: $fresh,
                before: $before,
                after: $fresh->toArray(),
                metadata: ['message' => $data['message'] ?? null],
            );

            if ($newStatus === 'completed') {
                app(TripWizardCostProvisioner::class)->provision($fresh, $user->id);
            }

            return $this->ok($fresh);
        });

        if ($newStatus === 'completed' && $beforeStatus !== 'completed') {
            $trip->refresh();
            app(DispatchRecurringMaintenanceService::class)->consumePackageSessionAfterTripCompletion($trip);
            app(RecurringBudgetAlertService::class)->refreshAndNotifyForTrip($trip);
        }

        if (in_array($newStatus, ['cancelled'], true) && $beforeStatus !== 'cancelled') {
            $trip->refresh();
            app(RecurringBudgetAlertService::class)->refreshAndNotifyForTrip($trip);
            app(TripDriverNotifyService::class)->notifyDriversCancelled($trip);
        }

        return $response;
    }

    public function addEvent(AddTripEventRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

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

    public function deleteEvent(DeleteTripEventRequest $request, Trip $trip, TripEvent $tripEvent)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);
        abort_unless((int) $tripEvent->trip_id === (int) $trip->id, 404);
        abort_unless($tripEvent->type === 'note', 422, 'Chỉ được xóa ghi chú điều vận.');

        $user = $request->user();
        $before = $tripEvent->toArray();

        DB::transaction(function () use ($trip, $tripEvent, $user, $before) {
            $tripEvent->delete();

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'trip.event.delete',
                auditable: $trip,
                before: $before,
                after: null,
                metadata: ['event_id' => $before['id'] ?? null, 'type' => 'note'],
            );
        });

        return $this->ok(['deleted' => true]);
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
