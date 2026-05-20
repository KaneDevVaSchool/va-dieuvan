<?php

namespace App\Services\P2pPolicy;

use App\Models\DispatchRequest;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyTripSlot;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use App\Services\Dispatching\DispatchingService;
use App\Services\Trips\TripNamedPassengerSyncService;
use App\Support\P2pPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class P2pPolicyTripMaterializer
{
    public function __construct(
        private readonly P2pPolicyRosterResolver $roster,
        private readonly P2pPolicyGenerationPlanner $planner,
        private readonly P2pPolicyCalendar $calendar,
        private readonly DispatchingService $dispatching,
        private readonly TripNamedPassengerSyncService $passengers,
    ) {}

    /**
     * @return 'created'|'skipped'
     */
    public function materializeSlot(
        P2pPolicyTerm $term,
        PolicyRoute $route,
        Carbon $runDate,
        string $leg,
        int $activatorUserId,
    ): string {
        $exists = PolicyTripSlot::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('policy_route_id', $route->id)
            ->whereDate('run_date', $runDate->toDateString())
            ->where('leg', $leg)
            ->exists();

        if ($exists) {
            return 'skipped';
        }

        $term->loadMissing(['holidays', 'skipDates']);
        if ($this->calendar->shouldSkipDate($term, $runDate)) {
            return 'skipped';
        }

        $legs = $this->roster->legsForRouteOnDate($route, $runDate);
        if (! in_array($leg, $legs, true)) {
            return 'skipped';
        }

        $window = $this->planner->legWindow($route, $term, $leg, $runDate);
        if ($window === null) {
            return 'skipped';
        }

        $route->loadMissing(['originCampus', 'destCampus']);
        $students = $this->roster->activeStudentsForRouteOnDate($route, $runDate);
        if ($students->isEmpty()) {
            return 'skipped';
        }

        if ($route->vehicle_id === null || $route->driver_id === null) {
            return 'skipped';
        }

        $origin = $route->originCampus?->name ?? 'Campus';
        $dest = $route->destCampus?->name ?? 'Campus';

        if ($leg === P2pPolicy::LEG_MORNING) {
            $drOrigin = $origin;
            $drDest = $dest;
        } else {
            $drOrigin = $dest;
            $drDest = $origin;
        }

        $passengerRows = [];
        foreach ($students as $ps) {
            $passengerRows[] = [
                'name' => $ps->student_name,
                'phone' => null,
                'note' => trim(($ps->class_name ?? '').' · '.$ps->student_code),
            ];
        }

        return DB::transaction(function () use (
            $term,
            $route,
            $runDate,
            $leg,
            $activatorUserId,
            $window,
            $drOrigin,
            $drDest,
            $passengerRows,
            $students,
        ): string {
            $dispatchRequest = DispatchRequest::create([
                'requester_id' => $activatorUserId,
                'approved_by' => $activatorUserId,
                'source_channel' => P2pPolicy::SOURCE_CHANNEL,
                'trip_type' => 'point_to_point',
                'origin' => $drOrigin,
                'destination' => $drDest,
                'depart_at' => $window['start'],
                'arrive_by' => $window['end'],
                'passenger_count' => $students->count(),
                'status' => 'approved',
                'paper_status' => 'pending',
                'wizard_snapshot' => [
                    'form' => [
                        'point_purpose_kind' => P2pPolicy::PURPOSE_KIND,
                        'p2p_policy_term_id' => $term->id,
                        'policy_route_id' => $route->id,
                        'leg' => $leg,
                        'backup_driver_id' => $route->backup_driver_id,
                    ],
                    'passengerRows' => [],
                ],
            ]);

            $trip = Trip::create([
                'dispatch_request_id' => $dispatchRequest->id,
                'dispatcher_id' => $activatorUserId,
                'status' => 'approved',
                'depart_at' => $window['start'],
                'arrive_by' => $window['end'],
                'lock_version' => 0,
            ]);

            $this->passengers->replace(
                $trip,
                $dispatchRequest,
                $students->count(),
                $passengerRows,
            );

            $this->dispatching->assignResources($trip, [
                'lock_version' => 0,
                'vehicle_id' => $route->vehicle_id,
                'driver_id' => $route->driver_id,
                'dispatcher_id' => $activatorUserId,
                'actor_id' => $activatorUserId,
                'suppress_assignment_notifications' => true,
            ]);

            PolicyTripSlot::create([
                'p2p_policy_term_id' => $term->id,
                'policy_route_id' => $route->id,
                'run_date' => $runDate->toDateString(),
                'leg' => $leg,
                'dispatch_request_id' => $dispatchRequest->id,
                'trip_id' => $trip->id,
            ]);

            app(AuditLogger::class)->log(
                actorId: $activatorUserId,
                event: 'p2p_policy.trip.materialized',
                auditable: $term,
                before: null,
                after: [
                    'policy_route_id' => $route->id,
                    'run_date' => $runDate->toDateString(),
                    'leg' => $leg,
                    'trip_id' => $trip->id,
                    'dispatch_request_id' => $dispatchRequest->id,
                ],
            );

            return 'created';
        });
    }
}
