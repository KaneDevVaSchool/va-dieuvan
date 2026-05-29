<?php

namespace App\Services\Dispatching;

use App\Models\DispatchRequest;
use App\Models\Trip;
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

        $out = [];
        foreach ($defs as $def) {
            $key = $def['key'];
            $assign = $assignByKey[$key] ?? null;
            $out[] = array_merge($def, [
                'assignment' => is_array($assign) ? $assign : null,
                'assigned' => $this->legAssignmentIsComplete(is_array($assign) ? $assign : null),
            ]);
        }

        return $out;
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
        if ($val === null || $val === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $val)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{taxis: list<array>, vendors: list<array>}|null
     */
    private function normalizeSupplementArray(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $taxis = array_values(array_filter($raw['taxis'] ?? [], 'is_array'));
        $vendors = array_values(array_filter($raw['vendors'] ?? [], 'is_array'));
        if ($taxis === [] && $vendors === []) {
            return null;
        }

        return ['taxis' => $taxis, 'vendors' => $vendors];
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
