<?php

namespace App\Services\Dispatching;

use App\Models\Trip;
use App\Support\TripScheduleInstant;
use Illuminate\Support\Carbon;

class TripScheduleLegService
{
    /**
     * @return list<array{key: string, variant: string, row_index: int, depart_at: ?string, arrive_by: ?string, pickup: string, dropoff: string, waypoint: string, label_seq: int}>
     */
    public function buildLegDefinitionsFromSnapshot(?array $snapshot, string $tripType): array
    {
        if (! is_array($snapshot)) {
            return [];
        }

        $legs = [];
        $seq = 0;

        if ($tripType === 'cargo') {
            foreach (array_values($snapshot['cargoRows'] ?? []) as $idx => $row) {
                if (! is_array($row) || ! $this->isCargoRowFilled($row)) {
                    continue;
                }
                $seq++;
                $legs[] = $this->legFromCargoRow($row, $idx, $seq);
            }

            return $legs;
        }

        if ($tripType !== 'business') {
            foreach (array_values($snapshot['passengerRows'] ?? []) as $idx => $row) {
                if (! is_array($row) || ! $this->isPassengerRowFilled($row)) {
                    continue;
                }
                $seq++;
                $legs[] = $this->legFromPassengerRow($row, $idx, $seq);
            }
        }

        if ($tripType !== 'point_to_point') {
            foreach (array_values($snapshot['businessRows'] ?? []) as $idx => $row) {
                if (! is_array($row) || ! $this->isBusinessRowFilled($row)) {
                    continue;
                }
                $seq++;
                $legs[] = $this->legFromBusinessRow($row, $idx, $seq);
            }
        }

        return $legs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveScheduleLegsForTrip(Trip $trip): array
    {
        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        if (! $dr) {
            return [];
        }

        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : null,
            (string) ($dr->trip_type ?? ''),
        );

        $assignByKey = [];
        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = (string) ($item['key'] ?? '');
            if ($key !== '') {
                $assignByKey[$key] = $item;
            }
        }

        $multiLeg = count($defs) > 1;
        $tripStatus = (string) ($trip->status ?? 'pending');

        $out = [];
        foreach ($defs as $def) {
            $key = $def['key'];
            $assign = $assignByKey[$key] ?? null;
            $assignArr = is_array($assign) ? $assign : null;
            $out[] = array_merge($def, [
                'assignment' => $assignArr,
                'assigned' => $this->legAssignmentIsComplete($assignArr),
                'status' => $this->effectiveLegStatus($assignArr, $tripStatus, $multiLeg),
                'started_at' => $assignArr['started_at'] ?? null,
                'completed_at' => $assignArr['completed_at'] ?? null,
            ]);
        }

        return $out;
    }

    /**
     * Trạng thái vận hành hiển thị của từng lịch (lưu tại schedule_assignments[].status).
     */
    public function effectiveLegStatus(?array $assignment, string $tripStatus, bool $multiLeg): string
    {
        if (is_array($assignment) && ! empty($assignment['status'])) {
            return (string) $assignment['status'];
        }

        if (! $multiLeg) {
            return $tripStatus;
        }

        if (in_array($tripStatus, ['completed', 'cancelled', 'incident'], true)) {
            return $tripStatus;
        }

        if ($tripStatus === 'in_progress') {
            return 'driver_confirmed';
        }

        return $tripStatus;
    }

    public function driverAssignedToLeg(Trip $trip, int $driverId, string $scheduleKey): bool
    {
        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            if ((string) ($item['key'] ?? '') !== $scheduleKey) {
                continue;
            }

            return (int) ($item['driver_id'] ?? 0) === $driverId;
        }

        return false;
    }

    /**
     * Trạng thái nguồn để validate chuyển bước khi đổi theo một lịch (schedule_key).
     */
    public function resolveLegStatusForTransition(Trip $trip, string $scheduleKey): ?string
    {
        $trip->loadMissing('dispatchRequest');
        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($trip->dispatchRequest?->wizard_snapshot) ? $trip->dispatchRequest->wizard_snapshot : null,
            (string) ($trip->dispatchRequest?->trip_type ?? ''),
        );
        if (count($defs) <= 1) {
            return null;
        }

        abort_unless($this->legKeyInDefinitions($defs, $scheduleKey), 422, 'Lịch trình không hợp lệ.');

        $assignments = array_values(is_array($trip->schedule_assignments) ? $trip->schedule_assignments : []);
        $assign = $this->findAssignmentByKey($assignments, $scheduleKey);

        return $this->effectiveLegStatus($assign, (string) $trip->status, true);
    }

    /**
     * @return array{trip: array<string, mixed>, schedule_assignments: list<array<string, mixed>>|null}
     */
    public function applyStatusChange(Trip $trip, string $newStatus, ?string $scheduleKey): array
    {
        $trip->loadMissing('dispatchRequest');
        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($trip->dispatchRequest?->wizard_snapshot) ? $trip->dispatchRequest->wizard_snapshot : null,
            (string) ($trip->dispatchRequest?->trip_type ?? ''),
        );
        $multiLeg = count($defs) > 1;

        if (! $multiLeg) {
            return [
                'trip' => $this->wholeTripStatusFieldUpdates($trip, $newStatus),
                'schedule_assignments' => null,
            ];
        }

        $assignments = array_values(is_array($trip->schedule_assignments) ? $trip->schedule_assignments : []);

        if ($scheduleKey === null || $scheduleKey === '') {
            $assignments = $this->setAllLegsStatus($assignments, $defs, $newStatus);

            return [
                'trip' => $this->wholeTripStatusFieldUpdates($trip, $newStatus, $assignments),
                'schedule_assignments' => $assignments,
            ];
        }

        abort_unless($this->legKeyInDefinitions($defs, $scheduleKey), 422, 'Lịch trình không hợp lệ.');
        $assignments = $this->setLegStatus($assignments, $scheduleKey, $newStatus);
        $aggregated = $this->aggregateTripStatusFromLegs($assignments, $defs, (string) $trip->status);

        $tripUpdates = ['status' => $aggregated];
        if ($aggregated === 'in_progress') {
            $tripUpdates['started_at'] = $trip->started_at ?? now();
        }
        if ($aggregated === 'completed') {
            $tripUpdates['completed_at'] = $trip->completed_at ?? now();
        } elseif ($aggregated !== 'cancelled') {
            $tripUpdates['completed_at'] = null;
        }

        return [
            'trip' => $tripUpdates,
            'schedule_assignments' => $assignments,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $defs
     * @return list<array<string, mixed>>
     */
    private function setAllLegsStatus(array $assignments, array $defs, string $newStatus): array
    {
        foreach ($defs as $def) {
            $key = (string) $def['key'];
            $assignments = $this->setLegStatus($assignments, $key, $newStatus);
        }

        return $assignments;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return list<array<string, mixed>>
     */
    private function setLegStatus(array $assignments, string $scheduleKey, string $newStatus): array
    {
        $now = now()->toIso8601String();
        $found = false;

        foreach ($assignments as $i => $item) {
            if (! is_array($item) || (string) ($item['key'] ?? '') !== $scheduleKey) {
                continue;
            }
            $item['status'] = $newStatus;
            if ($newStatus === 'in_progress' && empty($item['started_at'])) {
                $item['started_at'] = $now;
            }
            if ($newStatus === 'completed') {
                $item['completed_at'] = $now;
            }
            if ($newStatus === 'cancelled') {
                $item['completed_at'] = $item['completed_at'] ?? $now;
            }
            $assignments[$i] = $item;
            $found = true;
            break;
        }

        abort_unless($found, 422, 'Chưa phân công lịch trình: '.$scheduleKey);

        return $assignments;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $defs
     */
    private function aggregateTripStatusFromLegs(array $assignments, array $defs, string $tripStatusFallback): string
    {
        $statuses = [];
        foreach ($defs as $def) {
            $assign = $this->findAssignmentByKey($assignments, (string) $def['key']);
            $statuses[] = $this->effectiveLegStatus($assign, $tripStatusFallback, true);
        }

        if ($statuses === []) {
            return $tripStatusFallback;
        }

        if (count(array_filter($statuses, fn ($s) => $s === 'incident')) > 0) {
            return 'incident';
        }

        if (count(array_filter($statuses, fn ($s) => $s === 'cancelled')) === count($statuses)) {
            return 'cancelled';
        }

        $completedCount = count(array_filter($statuses, fn ($s) => $s === 'completed'));
        if ($completedCount === count($statuses)) {
            return 'completed';
        }

        if (in_array('in_progress', $statuses, true) || ($completedCount > 0 && $completedCount < count($statuses))) {
            return 'in_progress';
        }

        if (in_array('driver_confirmed', $statuses, true)) {
            return 'driver_confirmed';
        }

        if (in_array('assigned', $statuses, true)) {
            return 'assigned';
        }

        return $tripStatusFallback;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     */
    private function findAssignmentByKey(array $assignments, string $key): ?array
    {
        foreach ($assignments as $item) {
            if (is_array($item) && (string) ($item['key'] ?? '') === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $defs
     */
    private function legKeyInDefinitions(array $defs, string $scheduleKey): bool
    {
        foreach ($defs as $def) {
            if ((string) ($def['key'] ?? '') === $scheduleKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>|null  $assignmentsAfterLegUpdate
     * @return array<string, mixed>
     */
    private function wholeTripStatusFieldUpdates(Trip $trip, string $newStatus, ?array $assignmentsAfterLegUpdate = null): array
    {
        $updates = ['status' => $newStatus];
        if ($newStatus === 'in_progress') {
            $updates['started_at'] = $trip->started_at ?? now();
        }
        if (in_array($newStatus, ['completed', 'cancelled'], true)) {
            $updates['completed_at'] = $trip->completed_at ?? now();
        }

        return $updates;
    }

    /**
     * @param  list<array<string, mixed>>  $payloadLegs
     * @return list<array<string, mixed>>
     */
    public function normalizeScheduleAssignmentsPayload(Trip $trip, array $payloadLegs): array
    {
        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        abort_if(! $dr, 422, 'Chuyến không gắn yêu cầu điều xe.');

        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : null,
            (string) ($dr->trip_type ?? ''),
        );

        if ($defs === []) {
            return [];
        }

        $byKey = [];
        foreach ($payloadLegs as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = (string) ($item['key'] ?? '');
            if ($key !== '') {
                $byKey[$key] = $item;
            }
        }

        $normalized = [];
        foreach ($defs as $def) {
            $key = $def['key'];
            if (! isset($byKey[$key])) {
                abort(422, 'Thiếu phân công cho lịch trình: '.$key);
            }
            $p = $byKey[$key];
            abort_unless($this->legPayloadIsComplete($p), 422, 'Phân công chưa đủ cho lịch: '.$key);

            $normalized[] = array_merge([
                'key' => $key,
                'variant' => $def['variant'],
                'row_index' => $def['row_index'],
                'depart_at' => $def['depart_at'],
                'arrive_by' => $def['arrive_by'],
            ], $this->extractAssignmentFields($p));
        }

        return $normalized;
    }

    /**
     * Legacy single payload → one assignment for sole/first leg.
     *
     * @return list<array<string, mixed>>
     */
    public function legacySingleAssignmentFromPayload(Trip $trip, array $payload): array
    {
        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($dr?->wizard_snapshot) ? $dr->wizard_snapshot : null,
            (string) ($dr?->trip_type ?? ''),
        );

        $fields = $this->extractAssignmentFields($payload);
        if ($defs === []) {
            $departAt = $trip->depart_at instanceof Carbon
                ? $trip->depart_at->toIso8601String()
                : (string) ($trip->depart_at ?? '');
            $arriveBy = $trip->arrive_by instanceof Carbon
                ? $trip->arrive_by->toIso8601String()
                : ($trip->arrive_by ? (string) $trip->arrive_by : null);

            return [array_merge([
                'key' => 'legacy:0',
                'variant' => 'legacy',
                'row_index' => 0,
                'depart_at' => $departAt ?: null,
                'arrive_by' => $arriveBy,
            ], $fields)];
        }

        $def = $defs[0];
        $normalized = [array_merge([
            'key' => $def['key'],
            'variant' => $def['variant'],
            'row_index' => $def['row_index'],
            'depart_at' => $def['depart_at'],
            'arrive_by' => $def['arrive_by'],
        ], $fields)];

        if (count($defs) > 1) {
            abort(422, 'Yêu cầu có nhiều lịch trình — gửi schedule_assignments cho từng lịch.');
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     */
    public function allLegsAssigned(array $assignments, Trip $trip): bool
    {
        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        $defs = $this->buildLegDefinitionsFromSnapshot(
            is_array($dr?->wizard_snapshot) ? $dr->wizard_snapshot : null,
            (string) ($dr?->trip_type ?? ''),
        );
        if ($defs === []) {
            return count($assignments) > 0 && $this->legAssignmentIsComplete($assignments[0] ?? null);
        }

        if (count($assignments) !== count($defs)) {
            return false;
        }

        foreach ($assignments as $a) {
            if (! $this->legAssignmentIsComplete(is_array($a) ? $a : null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return array{vehicle_id: ?int, driver_id: ?int, transport_provider_id: ?int, external_vehicle_ref: ?string, external_driver_ref: ?string, supplement_transports: ?string}
     */
    public function primaryTripColumnsFromAssignments(array $assignments): array
    {
        $first = $assignments[0] ?? null;
        if (! is_array($first)) {
            return [
                'vehicle_id' => null,
                'driver_id' => null,
                'transport_provider_id' => null,
                'external_vehicle_ref' => null,
                'external_driver_ref' => null,
                'supplement_transports' => null,
            ];
        }

        return [
            'vehicle_id' => isset($first['vehicle_id']) ? (int) $first['vehicle_id'] : null,
            'driver_id' => isset($first['driver_id']) ? (int) $first['driver_id'] : null,
            'transport_provider_id' => isset($first['transport_provider_id']) ? (int) $first['transport_provider_id'] : null,
            'external_vehicle_ref' => $first['external_vehicle_ref'] ?? null,
            'external_driver_ref' => $first['external_driver_ref'] ?? null,
            'supplement_transports' => $this->encodeSupplement($first['supplement_transports'] ?? null),
        ];
    }

    public function legTimeWindow(array $legDef): array
    {
        $departRaw = $legDef['depart_at'] ?? null;
        $arriveRaw = $legDef['arrive_by'] ?? null;
        $departAt = $departRaw ? Carbon::parse($departRaw) : Carbon::now();
        $arriveBy = $arriveRaw
            ? Carbon::parse($arriveRaw)
            : $departAt->copy()->addHours(2);

        return [$departAt, $arriveBy];
    }

    /**
     * Khung giờ chặng: merge định nghĩa + phân công, fallback cột chuyến (khớp FE tripScheduleConflict).
     *
     * @param  array<string, mixed>  $assign
     * @param  array<string, mixed>|null  $def
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveLegTimeWindow(Trip $trip, array $assign, ?array $def = null): array
    {
        $merged = array_merge($def ?? [], $assign);

        $departRaw = $merged['depart_at'] ?? null;
        if (($departRaw === null || $departRaw === '') && $trip->depart_at !== null) {
            $departRaw = $trip->depart_at instanceof Carbon
                ? $trip->depart_at->toIso8601String()
                : (string) $trip->depart_at;
        }

        $departAt = ($departRaw !== null && $departRaw !== '')
            ? Carbon::parse((string) $departRaw)
            : Carbon::now();

        $arriveRaw = $merged['arrive_by'] ?? null;
        if ($arriveRaw !== null && $arriveRaw !== '') {
            $arriveBy = Carbon::parse((string) $arriveRaw);
        } elseif ($trip->arrive_by !== null) {
            $arriveBy = $trip->arrive_by instanceof Carbon
                ? $trip->arrive_by->copy()
                : Carbon::parse((string) $trip->arrive_by);
        } else {
            $arriveBy = $departAt->copy()->addHours(2);
        }

        return [$departAt, $arriveBy];
    }

    /**
     * Khung giờ phân bổ tài xế/xe theo từng lịch (tránh coi driver_id cấp chuyến là bận cả ngày).
     *
     * @return list<array{driver_id: ?int, vehicle_id: ?int, start: Carbon, end: Carbon}>
     */
    public function resourceOccupancySlots(Trip $trip): array
    {
        $trip->loadMissing('dispatchRequest');
        $assignments = array_values(is_array($trip->schedule_assignments) ? $trip->schedule_assignments : []);

        if ($assignments !== []) {
            $defsByKey = [];
            $dr = $trip->dispatchRequest;
            if ($dr) {
                foreach ($this->buildLegDefinitionsFromSnapshot(
                    is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : null,
                    (string) ($dr->trip_type ?? ''),
                ) as $def) {
                    $defsByKey[$def['key']] = $def;
                }
            }

            $slots = [];
            foreach ($assignments as $assign) {
                if (! is_array($assign)) {
                    continue;
                }
                $key = (string) ($assign['key'] ?? '');
                $def = $defsByKey[$key] ?? null;
                [$start, $end] = $this->resolveLegTimeWindow($trip, $assign, is_array($def) ? $def : null);
                $slots[] = [
                    'driver_id' => isset($assign['driver_id']) ? (int) $assign['driver_id'] : null,
                    'vehicle_id' => isset($assign['vehicle_id']) ? (int) $assign['vehicle_id'] : null,
                    'start' => $start,
                    'end' => $end,
                ];
            }

            return $slots;
        }

        $departAt = $trip->depart_at instanceof Carbon ? $trip->depart_at : Carbon::parse($trip->depart_at);
        $arriveBy = $trip->arrive_by
            ? ($trip->arrive_by instanceof Carbon ? $trip->arrive_by : Carbon::parse($trip->arrive_by))
            : $departAt->copy()->addHours(2);

        return [[
            'driver_id' => $trip->driver_id !== null ? (int) $trip->driver_id : null,
            'vehicle_id' => $trip->vehicle_id !== null ? (int) $trip->vehicle_id : null,
            'start' => $departAt,
            'end' => $arriveBy,
        ]];
    }

    /**
     * Slots JSON cho client kiểm tra trùng lịch (khớp resourceOccupancySlots).
     *
     * @return list<array{driver_id: ?int, vehicle_id: ?int, start: string, end: string}>
     */
    public function occupancySlotsForApi(Trip $trip): array
    {
        $out = [];
        foreach ($this->resourceOccupancySlots($trip) as $slot) {
            $out[] = [
                'driver_id' => $slot['driver_id'],
                'vehicle_id' => $slot['vehicle_id'],
                'start' => $slot['start']->toIso8601String(),
                'end' => $slot['end']->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    public function uniqueDriverIdsFromAssignments(?array $assignments): array
    {
        $ids = [];
        foreach (is_array($assignments) ? $assignments : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = $item['driver_id'] ?? null;
            if ($id !== null && (int) $id > 0) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }

    public function driverAssignedToAnyLeg(Trip $trip, int $driverId): bool
    {
        if ((int) $trip->driver_id === $driverId) {
            return true;
        }

        foreach (is_array($trip->schedule_assignments) ? $trip->schedule_assignments : [] as $item) {
            if (is_array($item) && (int) ($item['driver_id'] ?? 0) === $driverId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legFromPassengerRow(array $row, int $idx, int $seq): array
    {
        return [
            'key' => 'passenger:'.$idx,
            'variant' => 'passenger',
            'row_index' => $idx,
            'label_seq' => $seq,
            'depart_at' => $this->isoOrNull($row['depart_at'] ?? null),
            'arrive_by' => $this->isoOrNull($row['return_at'] ?? null),
            'pickup' => trim((string) ($row['pickup'] ?? '')),
            'dropoff' => trim((string) ($row['dropoff'] ?? '')),
            'waypoint' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legFromBusinessRow(array $row, int $idx, int $seq): array
    {
        return [
            'key' => 'business:'.$idx,
            'variant' => 'business',
            'row_index' => $idx,
            'label_seq' => $seq,
            'depart_at' => $this->isoOrNull($row['depart_at'] ?? null),
            'arrive_by' => $this->isoOrNull($row['return_at'] ?? null),
            'pickup' => trim((string) ($row['pickup'] ?? '')),
            'dropoff' => trim((string) ($row['dropoff'] ?? '')),
            'waypoint' => trim((string) ($row['waypoint'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legFromCargoRow(array $row, int $idx, int $seq): array
    {
        return [
            'key' => 'cargo:'.$idx,
            'variant' => 'cargo',
            'row_index' => $idx,
            'label_seq' => $seq,
            'depart_at' => $this->isoOrNull($row['pickup_at'] ?? null),
            'arrive_by' => $this->isoOrNull($row['delivery_at'] ?? null),
            'pickup' => trim((string) ($row['pickup_place'] ?? '')),
            'dropoff' => trim((string) ($row['delivery_place'] ?? '')),
            'waypoint' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractAssignmentFields(array $payload): array
    {
        return [
            'vehicle_id' => isset($payload['vehicle_id']) ? (int) $payload['vehicle_id'] : null,
            'driver_id' => isset($payload['driver_id']) ? (int) $payload['driver_id'] : null,
            'transport_provider_id' => isset($payload['transport_provider_id']) ? (int) $payload['transport_provider_id'] : null,
            'external_vehicle_ref' => isset($payload['external_vehicle_ref']) ? substr((string) $payload['external_vehicle_ref'], 0, 255) : null,
            'external_driver_ref' => isset($payload['external_driver_ref']) ? substr((string) $payload['external_driver_ref'], 0, 255) : null,
            'supplement_transports' => $this->normalizeSupplementArray($payload['supplement_transports'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $assign
     */
    public function legAssignmentIsComplete(?array $assign): bool
    {
        if (! is_array($assign)) {
            return false;
        }

        $vehicleId = (int) ($assign['vehicle_id'] ?? 0);
        $driverId = (int) ($assign['driver_id'] ?? 0);
        $providerId = (int) ($assign['transport_provider_id'] ?? 0);
        $extV = trim((string) ($assign['external_vehicle_ref'] ?? ''));
        $extD = trim((string) ($assign['external_driver_ref'] ?? ''));

        if ($vehicleId > 0 && $driverId > 0) {
            return true;
        }
        if ($providerId > 0) {
            return true;
        }
        if ($extV !== '' || $extD !== '') {
            return true;
        }

        $sup = $assign['supplement_transports'] ?? null;
        if (is_array($sup)) {
            $taxis = $sup['taxis'] ?? [];
            $vendors = $sup['vendors'] ?? [];
            if ((is_array($taxis) && count($taxis) > 0) || (is_array($vendors) && count($vendors) > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function legPayloadIsComplete(array $payload): bool
    {
        return $this->legAssignmentIsComplete($this->extractAssignmentFields($payload));
    }

    private function isoOrNull(mixed $val): ?string
    {
        return TripScheduleInstant::toIso8601String($val);
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{taxis: list<array>, vendors: list<array>, internal_vehicles: list<array>, internal_drivers: list<array>}|null
     */
    private function normalizeSupplementArray(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $taxis = array_values(array_filter($raw['taxis'] ?? [], 'is_array'));
        $vendors = array_values(array_filter($raw['vendors'] ?? [], 'is_array'));
        $internalVehicles = array_values(array_filter($raw['internal_vehicles'] ?? [], 'is_array'));
        $internalDrivers = array_values(array_filter($raw['internal_drivers'] ?? [], 'is_array'));
        if ($taxis === [] && $vendors === [] && $internalVehicles === [] && $internalDrivers === []) {
            return null;
        }

        return [
            'taxis' => $taxis,
            'vendors' => $vendors,
            'internal_vehicles' => $internalVehicles,
            'internal_drivers' => $internalDrivers,
        ];
    }

    private function encodeSupplement(mixed $raw): ?string
    {
        $norm = $this->normalizeSupplementArray($raw);
        if ($norm === null) {
            return null;
        }

        return json_encode($norm);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isPassengerRowFilled(array $row): bool
    {
        return trim((string) ($row['pickup'] ?? '')) !== ''
            || trim((string) ($row['dropoff'] ?? '')) !== ''
            || trim((string) ($row['person_in_charge'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBusinessRowFilled(array $row): bool
    {
        return trim((string) ($row['pickup'] ?? '')) !== ''
            || trim((string) ($row['dropoff'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isCargoRowFilled(array $row): bool
    {
        return trim((string) ($row['name'] ?? '')) !== '';
    }
}
