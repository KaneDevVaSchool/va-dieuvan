<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\Dispatching\TripScheduleLegService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DiagnoseTripScheduleCommand extends Command
{
    protected $signature = 'trips:diagnose-schedule
                            {trip : Trip ID (e.g. 8)}
                            {--driver= : Driver ID to probe for cross-trip conflicts}';

    protected $description = 'In JSON: legs, occupancy slots, same-day trips, schedule data quality flags';

    public function handle(TripScheduleLegService $legs): int
    {
        $tripId = (int) $this->argument('trip');
        $probeDriverId = $this->option('driver') !== null ? (int) $this->option('driver') : null;

        $trip = Trip::query()
            ->with(['dispatchRequest', 'driver:id,full_name', 'vehicle:id,license_plate'])
            ->find($tripId);

        if (! $trip instanceof Trip) {
            $this->line(json_encode(['error' => 'trip_not_found', 'trip_id' => $tripId], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $dr = $trip->dispatchRequest;
        $snap = is_array($dr?->wizard_snapshot) ? $dr->wizard_snapshot : null;
        $defs = $legs->buildLegDefinitionsFromSnapshot($snap, (string) ($dr?->trip_type ?? ''));
        $occupancySelf = $legs->occupancySlotsForApi($trip);

        $day = $trip->depart_at instanceof Carbon
            ? $trip->depart_at->copy()->startOfDay()
            : Carbon::parse($trip->depart_at)->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $others = Trip::query()
            ->where('id', '!=', $trip->id)
            ->whereIn('status', ['assigned', 'driver_confirmed', 'in_progress'])
            ->whereBetween('depart_at', [$day, $dayEnd])
            ->with(['dispatchRequest:id,wizard_snapshot,trip_type', 'driver:id,full_name', 'vehicle:id,license_plate'])
            ->orderBy('depart_at')
            ->get(['id', 'status', 'driver_id', 'vehicle_id', 'depart_at', 'arrive_by', 'schedule_assignments', 'dispatch_request_id']);

        $internalOverlaps = $this->detectInternalOverlaps($trip, $legs, $defs);

        $externalConflicts = [];
        if ($probeDriverId !== null && $probeDriverId > 0) {
            $externalConflicts = $this->probeDriverConflicts($legs, $occupancySelf, $others, $probeDriverId);
        }

        $legsMissingTimes = [];
        foreach ($defs as $def) {
            if (empty($def['depart_at'])) {
                $legsMissingTimes[] = $def['key'];
            }
        }

        $assignMissingTimes = [];
        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $a) {
            if (! is_array($a)) {
                continue;
            }
            if (empty($a['depart_at'])) {
                $assignMissingTimes[] = (string) ($a['key'] ?? '?');
            }
        }

        $payload = [
            'trip' => [
                'id' => $trip->id,
                'status' => $trip->status,
                'depart_at' => $trip->depart_at?->toIso8601String(),
                'arrive_by' => $trip->arrive_by?->toIso8601String(),
                'driver_id' => $trip->driver_id,
                'driver_name' => $trip->driver?->full_name,
                'vehicle_id' => $trip->vehicle_id,
                'vehicle_plate' => $trip->vehicle?->license_plate,
                'schedule_assignments' => $trip->schedule_assignments,
            ],
            'dispatch_request' => [
                'id' => $dr?->id,
                'trip_type' => $dr?->trip_type,
                'origin' => $dr?->origin,
                'destination' => $dr?->destination,
            ],
            'leg_count' => count($defs),
            'leg_definitions' => $defs,
            'flags' => [
                'multi_leg' => count($defs) > 1,
                'snapshot_legs_missing_depart_at' => $legsMissingTimes,
                'assignments_missing_depart_at' => $assignMissingTimes,
                'had_time_window_bug_risk' => $legsMissingTimes !== [] || $assignMissingTimes !== [],
            ],
            'occupancy_slots_this_trip' => $occupancySelf,
            'internal_resource_overlaps' => $internalOverlaps,
            'same_day_other_trips' => $others->map(fn (Trip $o) => [
                'id' => $o->id,
                'status' => $o->status,
                'depart_at' => $o->depart_at?->toIso8601String(),
                'driver_id' => $o->driver_id,
                'driver_name' => $o->driver?->full_name,
                'vehicle_plate' => $o->vehicle?->license_plate,
                'occupancy_slots' => $legs->occupancySlotsForApi($o),
            ])->values()->all(),
            'probe_driver_id' => $probeDriverId,
            'external_driver_conflicts' => $externalConflicts,
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $defs
     * @return list<array<string, mixed>>
     */
    private function detectInternalOverlaps(Trip $trip, TripScheduleLegService $legs, array $defs): array
    {
        $assignByKey = [];
        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $k = (string) ($item['key'] ?? '');
            if ($k !== '') {
                $assignByKey[$k] = $item;
            }
        }

        $defsByKey = [];
        foreach ($defs as $def) {
            $defsByKey[$def['key']] = $def;
        }

        $windows = [];
        foreach ($assignByKey as $key => $assign) {
            $def = $defsByKey[$key] ?? null;
            [$start, $end] = $legs->resolveLegTimeWindow($trip, $assign, is_array($def) ? $def : null);
            $windows[] = [
                'key' => $key,
                'driver_id' => isset($assign['driver_id']) ? (int) $assign['driver_id'] : null,
                'vehicle_id' => isset($assign['vehicle_id']) ? (int) $assign['vehicle_id'] : null,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ];
        }

        $out = [];
        $n = count($windows);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $windows[$i];
                $b = $windows[$j];
                $aStart = Carbon::parse($a['start']);
                $aEnd = Carbon::parse($a['end']);
                $bStart = Carbon::parse($b['start']);
                $bEnd = Carbon::parse($b['end']);
                if (! $aStart->lt($bEnd) || ! $bStart->lt($aEnd)) {
                    continue;
                }
                if ($a['driver_id'] && $b['driver_id'] && $a['driver_id'] === $b['driver_id']) {
                    $out[] = ['kind' => 'driver', 'legs' => [$a['key'], $b['key']], 'windows' => [$a, $b]];
                }
                if ($a['vehicle_id'] && $b['vehicle_id'] && $a['vehicle_id'] === $b['vehicle_id']) {
                    $out[] = ['kind' => 'vehicle', 'legs' => [$a['key'], $b['key']], 'windows' => [$a, $b]];
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<array{driver_id: ?int, vehicle_id: ?int, start: string, end: string}>  $occupancySelf
     * @param  \Illuminate\Support\Collection<int, Trip>  $others
     * @return list<array<string, mixed>>
     */
    private function probeDriverConflicts(
        TripScheduleLegService $legs,
        array $occupancySelf,
        $others,
        int $probeDriverId,
    ): array {
        $conflicts = [];
        $windows = $occupancySelf !== [] ? $occupancySelf : [];

        foreach ($windows as $w) {
            $s = Carbon::parse($w['start']);
            $e = Carbon::parse($w['end']);
            foreach ($others as $other) {
                foreach ($legs->occupancySlotsForApi($other) as $os) {
                    if ((int) ($os['driver_id'] ?? 0) !== $probeDriverId) {
                        continue;
                    }
                    $osStart = Carbon::parse($os['start']);
                    $osEnd = Carbon::parse($os['end']);
                    if ($s->lt($osEnd) && $osStart->lt($e)) {
                        $conflicts[] = [
                            'other_trip_id' => $other->id,
                            'other_status' => $other->status,
                            'this_window' => $w,
                            'other_slot' => $os,
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }
}
