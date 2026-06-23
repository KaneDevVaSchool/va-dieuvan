<?php

namespace App\Services\Costs;

use App\Models\Trip;
use App\Models\User;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Dòng dự toán từ wizard_snapshot (chưa ghi trip_costs wizard_estimate khi hoàn thành chuyến).
 */
class WizardSnapshotCostLinesQuery
{
    public function __construct(
        private readonly WizardSnapshotCostEstimator $estimator,
    ) {}

    /**
     * @param  array{trip_id?: int, trip_type?: string, from?: string, to?: string, fleet_mode?: string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function linesFor(User $user, array $filters): array
    {
        $q = Trip::query()
            ->with([
                'dispatchRequest:id,trip_type,requester_id,wizard_snapshot,origin,destination,depart_at',
                'dispatchRequest.requester:id,name',
                'transportProvider:id,name,type',
                'vehicle:id,license_plate',
            ])
            ->whereHas('dispatchRequest')
            ->whereDoesntHave(
                'costs',
                fn (Builder $c) => $c->where('type', WizardSnapshotCostEstimator::PROVISION_TYPE),
            )
            ->orderByDesc('id');

        if (! $user->hasPermission('trip.view_all')) {
            $tripIds = TripVisibility::visibleTripsQuery($user)->pluck('id');
            $q->whereIn('id', $tripIds);
        }

        if (isset($filters['trip_id'])) {
            $q->where('id', (int) $filters['trip_id']);
        }

        if (! empty($filters['trip_type'])) {
            $q->whereHas(
                'dispatchRequest',
                fn (Builder $dr) => $dr->where('trip_type', $filters['trip_type']),
            );
        }

        if (! empty($filters['from'])) {
            $q->where('depart_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $q->where('depart_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['fleet_mode'])) {
            $q->where(function (Builder $trip) use ($filters) {
                match ($filters['fleet_mode']) {
                    'internal' => $trip->whereNull('transport_provider_id')->whereNotNull('vehicle_id'),
                    'vendor_hire' => $trip->whereNotNull('transport_provider_id')
                        ->whereHas('transportProvider', function (Builder $p) {
                            $p->where(function (Builder $inner) {
                                $inner->whereNull('type')->orWhere('type', '!=', 'taxi');
                            });
                        }),
                    'taxi' => $trip->whereNotNull('transport_provider_id')
                        ->whereHas('transportProvider', fn (Builder $p) => $p->where('type', 'taxi')),
                    'unspecified' => $trip->whereNull('transport_provider_id')->whereNull('vehicle_id'),
                    default => null,
                };
            });
        }

        $trips = $q->limit(200)->get();

        $out = [];
        foreach ($trips as $trip) {
            foreach ($this->linesForTrip($trip) as $line) {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Dòng dự toán cho một chuyến (kể cả đã provision trip_cost wizard_estimate).
     *
     * @return array<int, array<string, mixed>>
     */
    public function estimateLinesForTrip(Trip $trip): array
    {
        $trip->loadMissing([
            'dispatchRequest:id,trip_type,requester_id,wizard_snapshot,origin,destination,depart_at',
            'dispatchRequest.requester:id,name',
            'transportProvider:id,name,type',
            'vehicle:id,license_plate',
        ]);

        return $this->linesForTrip($trip);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function linesForTrip(Trip $trip): array
    {
        $dr = $trip->dispatchRequest;
        if ($dr === null) {
            return [];
        }

        $dr->makeVisible(['wizard_snapshot']);
        $snap = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : [];
        if ($this->estimator->totalFromSnapshot($snap) <= 0) {
            return [];
        }

        $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];
        $requesterName = $dr->requester?->name;
        $tripPayload = [
            'id' => $trip->id,
            'depart_at' => $trip->depart_at?->toIso8601String(),
            'transport_provider_id' => $trip->transport_provider_id,
            'vehicle_id' => $trip->vehicle_id,
            'transport_provider' => $trip->transportProvider ? [
                'id' => $trip->transportProvider->id,
                'name' => $trip->transportProvider->name,
                'type' => $trip->transportProvider->type,
            ] : null,
            'vehicle' => $trip->vehicle ? [
                'id' => $trip->vehicle->id,
                'license_plate' => $trip->vehicle->license_plate,
            ] : null,
            'dispatch_request' => [
                'id' => $dr->id,
                'trip_type' => $dr->trip_type,
                'origin' => $dr->origin,
                'destination' => $dr->destination,
                'requester' => $requesterName ? ['name' => $requesterName] : null,
            ],
        ];

        $base = [
            'trip_id' => $trip->id,
            'trip' => $tripPayload,
            'requester_name' => $requesterName,
            'is_wizard_estimate_line' => true,
            'type' => WizardSnapshotCostEstimator::PROVISION_TYPE,
            'currency' => 'VND',
            'status' => 'estimate',
            'created_at' => ($trip->depart_at ?? $dr->depart_at)?->toIso8601String(),
        ];

        $lines = [];

        $passNo = 0;
        foreach ($snap['passengerRows'] ?? [] as $row) {
            if (! is_array($row) || ! $this->isPassengerRowFilled($row)) {
                continue;
            }
            $passNo++;
            $lines[] = $this->rowLine($base, 'passenger', "passenger-{$passNo}", $row, $dr);
        }

        $busNo = 0;
        foreach ($snap['businessRows'] ?? [] as $row) {
            if (! is_array($row) || ! $this->isBusinessRowFilled($row)) {
                continue;
            }
            $busNo++;
            $lines[] = $this->rowLine($base, 'business', "business-{$busNo}", $row, $dr);
        }

        $cargoNo = 0;
        foreach ($snap['cargoRows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cost = $this->parseMoney($row['cost'] ?? null);
            if ($cost <= 0) {
                continue;
            }
            $cargoNo++;
            $desc = trim((string) ($row['description'] ?? $row['goods'] ?? ''));
            $lines[] = array_merge($base, [
                'line_key' => "cargo-{$cargoNo}",
                'estimate_kind' => 'cargo_row',
                'unit_price' => $cost,
                'extra_fee' => 0.0,
                'amount' => $cost,
                'description' => $desc !== '' ? $desc : "Hàng hóa #{$cargoNo}",
            ]);
        }

        $formExtras = [
            ['flag' => 'need_porters', 'field' => 'porter_cost', 'kind' => 'porter', 'label' => 'Bốc xếp / bốc hàng'],
            ['flag' => 'interprovincial', 'field' => 'interprovincial_cost', 'kind' => 'toll', 'label' => 'Cầu đường (dự kiến)'],
            ['flag' => 'e1_use_3plus_days', 'field' => 'e1_extra_cost', 'kind' => 'e1', 'label' => 'Phụ thu E.1'],
            ['flag' => 'e2_door_pickup', 'field' => 'e2_door_cost', 'kind' => 'e2_door', 'label' => 'E.2 đón cửa'],
            ['flag' => 'e2_driver_self', 'field' => 'e2_driver_self_cost', 'kind' => 'e2_self', 'label' => 'E.2 tự lái'],
            ['flag' => 'e2_after_21h', 'field' => 'e2_after_21h_cost', 'kind' => 'e2_late', 'label' => 'E.2 sau 21h'],
        ];
        foreach ($formExtras as $extra) {
            if (empty($form[$extra['flag']])) {
                continue;
            }
            $amt = $this->parseMoney($form[$extra['field']] ?? null);
            if ($amt <= 0) {
                continue;
            }
            $lines[] = array_merge($base, [
                'line_key' => 'form-'.$extra['kind'],
                'estimate_kind' => $extra['kind'],
                'unit_price' => $amt,
                'extra_fee' => 0.0,
                'amount' => $amt,
                'description' => $extra['label'],
            ]);
        }

        return $this->tagLegSequence($lines);
    }

    /**
     * Gắn số thứ tự chặng (leg_seq) cho từng dòng theo lịch trình khi chuyến có >1 chặng.
     * Các dòng phụ thu cấp form (bốc xếp, cầu đường…) là chi phí toàn chuyến → không gắn chặng.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function tagLegSequence(array $lines): array
    {
        $legKinds = ['passenger_row', 'business_row', 'cargo_row'];
        $legCount = count(array_filter(
            $lines,
            fn ($l) => in_array($l['estimate_kind'] ?? '', $legKinds, true),
        ));

        if ($legCount <= 1) {
            return $lines;
        }

        $seq = 0;
        foreach ($lines as $i => $line) {
            if (in_array($line['estimate_kind'] ?? '', $legKinds, true)) {
                $seq++;
                $lines[$i]['leg_seq'] = $seq;
            }
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $row
     * @param  \App\Models\DispatchRequest  $dr
     * @return array<string, mixed>
     */
    private function rowLine(array $base, string $source, string $lineKey, array $row, $dr): array
    {
        $unit = $this->parseMoney($row['unit_price'] ?? null);
        $extra = $this->parseMoney($row['extra_fee'] ?? null);
        $pickup = trim((string) ($row['pickup'] ?? ''));
        $dropoff = trim((string) ($row['dropoff'] ?? ''));
        $route = ($pickup !== '' || $dropoff !== '')
            ? trim("{$pickup} → {$dropoff}", ' →')
            : trim("{$dr->origin} → {$dr->destination}", ' →');

        $personnel = trim((string) ($row['description'] ?? ''));
        if ($personnel === '') {
            $personnel = trim((string) ($row['notes'] ?? ''));
        }

        $desc = $source === 'business'
            ? ($personnel !== '' ? $personnel : ($route !== '' ? $route : 'Dòng công tác'))
            : ($route !== '' ? $route : 'Dòng lịch hành khách');

        return array_merge($base, [
            'line_key' => $lineKey,
            'estimate_kind' => $source === 'business' ? 'business_row' : 'passenger_row',
            'unit_price' => $unit,
            'extra_fee' => $extra,
            'amount' => $unit + $extra,
            'description' => $desc,
            'pickup' => $pickup !== '' ? $pickup : null,
            'dropoff' => $dropoff !== '' ? $dropoff : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isPassengerRowFilled(array $row): bool
    {
        if ($this->parseMoney($row['unit_price'] ?? null) > 0) {
            return true;
        }
        if ($this->parseMoney($row['extra_fee'] ?? null) > 0) {
            return true;
        }
        if (trim((string) ($row['pickup'] ?? '')) !== ''
            || trim((string) ($row['dropoff'] ?? '')) !== ''
            || trim((string) ($row['depart_at'] ?? '')) !== '') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBusinessRowFilled(array $row): bool
    {
        if (trim((string) ($row['pickup'] ?? '')) !== ''
            || trim((string) ($row['dropoff'] ?? '')) !== ''
            || trim((string) ($row['waypoint'] ?? '')) !== ''
            || trim((string) ($row['depart_at'] ?? '')) !== ''
            || trim((string) ($row['return_at'] ?? '')) !== '') {
            return true;
        }
        if ($this->parseMoney($row['unit_price'] ?? null) > 0) {
            return true;
        }
        if ($this->parseMoney($row['extra_fee'] ?? null) > 0) {
            return true;
        }
        if (trim((string) ($row['notes'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($row['description'] ?? '')) !== '') {
            return true;
        }
        $g = trim((string) ($row['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    private function parseMoney(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return max(0.0, (float) $v);
        }
        $s = preg_replace('/[^\d.-]/', '', (string) $v) ?? '';
        if ($s === '' || ! is_numeric($s)) {
            return 0.0;
        }

        return max(0.0, (float) $s);
    }
}
