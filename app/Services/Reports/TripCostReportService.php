<?php

namespace App\Services\Reports;

use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use App\Services\Costs\WizardSnapshotCostLinesQuery;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TripCostReportService
{
    /** @var array<int, array<string, array{seq:int, route:string}>> Cache leg-map theo trip id (chỉ chuyến >1 chặng). */
    private array $legMapCache = [];

    public const STATUS_LABELS = [
        'draft' => 'Nháp',
        'submitted' => 'Chờ duyệt',
        'confirmed' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'estimate' => 'Ước tính phiếu',
    ];

    public const TRIP_TYPE_LABELS = [
        'point_to_point' => 'Điểm — Điểm',
        'business' => 'Công tác',
        'cargo' => 'Hàng hóa',
        'door_to_door' => 'Đưa đón',
    ];

    public function __construct(
        private readonly WizardSnapshotCostLinesQuery $wizardQuery,
        private readonly TripScheduleLegService $scheduleLegs,
    ) {}

    /** Nhãn chặng hiển thị, vd. "Chặng 2". */
    private function legLabel(int $seq): string
    {
        return "Chặng {$seq}";
    }

    /**
     * Map khóa chặng → thứ tự + tuyến, cho chuyến có >1 chặng (cache theo trip id).
     * Chuyến đơn chặng trả về [] để chi phí không bị gắn nhãn chặng thừa.
     *
     * @return array<string, array{seq:int, route:string}>
     */
    private function legMapForTrip(?Trip $trip): array
    {
        if ($trip === null) {
            return [];
        }
        $tripId = (int) $trip->id;
        if (array_key_exists($tripId, $this->legMapCache)) {
            return $this->legMapCache[$tripId];
        }

        $dr = $trip->dispatchRequest;
        $defs = $this->scheduleLegs->buildLegDefinitionsFromSnapshot(
            is_array($dr?->wizard_snapshot) ? $dr->wizard_snapshot : null,
            (string) ($dr?->trip_type ?? ''),
        );

        $map = [];
        if (count($defs) > 1) {
            foreach ($defs as $def) {
                $key = (string) ($def['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $pickup = trim((string) ($def['pickup'] ?? ''));
                $dropoff = trim((string) ($def['dropoff'] ?? ''));
                $route = ($pickup !== '' || $dropoff !== '')
                    ? trim("{$pickup} → {$dropoff}", ' →')
                    : '';
                $map[$key] = [
                    'seq' => (int) ($def['label_seq'] ?? 0),
                    'route' => $route,
                ];
            }
        }

        return $this->legMapCache[$tripId] = $map;
    }

    /**
     * Aggregate statistics computed in-memory from merged rows.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statistics(User $user, array $filters): array
    {
        return $this->statisticsFromRows($this->rows($user, $filters));
    }

    /**
     * Unified rows from persisted trip_costs + wizard estimate lines.
     *
     * @param  array{status?:string,type?:string,trip_type?:string,trip_id?:int,from?:string,to?:string,provider?:string,fleet_mode?:string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function rows(User $user, array $filters): array
    {
        $recorded = $this->recordedRows($user, $filters);
        $estimated = $this->estimatedRows($user, $filters);

        $merged = array_merge($estimated, $recorded);

        // Client-side provider filter (since provider comes from relation, not column)
        if (! empty($filters['provider'])) {
            $want = $filters['provider'];
            $merged = array_values(array_filter(
                $merged,
                fn ($r) => ($r['provider'] ?? '') === $want,
            ));
        }

        // Đơn vị / phòng ban (lấy từ relation requester.department, không phải cột) → lọc in-memory.
        if (! empty($filters['unit'])) {
            $wantUnit = $filters['unit'];
            $merged = array_values(array_filter(
                $merged,
                fn ($r) => ($r['unit'] ?? '') === $wantUnit,
            ));
        }

        // Khoảng số tiền (thành tiền) — áp dụng sau khi gộp recorded + estimate.
        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $min = (float) $filters['min_amount'];
            $merged = array_values(array_filter($merged, fn ($r) => (float) ($r['amount'] ?? 0) >= $min));
        }
        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $max = (float) $filters['max_amount'];
            $merged = array_values(array_filter($merged, fn ($r) => (float) ($r['amount'] ?? 0) <= $max));
        }

        usort($merged, fn ($a, $b) => strcmp($b['sort_date'] ?? '', $a['sort_date'] ?? ''));

        return array_values($merged);
    }

    /**
     * Aggregate statistics computed in-memory from pre-fetched rows.
     * Pass the result of rows() to avoid double querying.
     *
     * @param  array<int, array<string, mixed>>  $rows  Output of rows()
     * @return array<string, mixed>
     */
    public function statisticsFromRows(array $rows): array
    {

        $totalAmount = 0.0;
        $totalUnitPrice = 0.0;
        $totalExtraFee = 0.0;
        $byStatus = [];
        $byCategory = [];
        $byProvider = [];
        $byMonth = [];
        $byUnit = [];

        foreach ($rows as $row) {
            $amt = (float) ($row['amount'] ?? 0);
            $up = (float) ($row['unit_price'] ?? 0);
            $ef = (float) ($row['extra_fee'] ?? 0);

            $totalAmount += $amt;
            $totalUnitPrice += $up;
            $totalExtraFee += $ef;

            $status = $row['status'] ?? 'draft';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + $amt;

            $cat = $row['category'] ?: 'other';
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + $amt;

            $provider = trim((string) ($row['provider'] ?? ''));
            if ($provider === '' || $provider === '—') {
                $provider = 'Xe nội bộ';
            }
            $byProvider[$provider] = ($byProvider[$provider] ?? 0) + $amt;

            $month = $row['month_key'] ?? '';
            if ($month !== '') {
                $byMonth[$month] = ($byMonth[$month] ?? 0) + $amt;
            }

            $unit = trim((string) ($row['unit'] ?? ''));
            if ($unit === '' || $unit === '—') {
                $unit = 'Không xác định';
            }
            $byUnit[$unit] = ($byUnit[$unit] ?? 0) + $amt;
        }

        ksort($byMonth);
        arsort($byProvider);
        arsort($byUnit);
        arsort($byCategory);

        return [
            'count' => count($rows),
            'total_amount' => $totalAmount,
            'total_unit_price' => $totalUnitPrice,
            'total_extra_fee' => $totalExtraFee,
            'by_status' => $this->pairsFromMap($byStatus, self::STATUS_LABELS),
            'by_category' => $this->pairsFromMap($byCategory, self::TRIP_TYPE_LABELS),
            'by_provider' => $this->numericPairs($byProvider),
            'by_month' => $this->monthPairs($byMonth),
            'by_unit' => $this->numericPairs($byUnit),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Private query helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recordedRows(User $user, array $filters): array
    {
        $q = TripCost::query()
            ->with([
                'trip:id,status,depart_at,transport_provider_id,vehicle_id,dispatch_request_id',
                'trip.dispatchRequest:id,trip_type,origin,destination,requester_id,wizard_snapshot',
                'trip.dispatchRequest.requester:id,name,department_id',
                'trip.dispatchRequest.requester.department:id,name',
                'trip.transportProvider:id,name,type',
                'trip.vehicle:id,license_plate',
                'creator:id,name',
            ])
            ->orderByDesc('id');

        if (! $user->hasPermission('trip.view_all')) {
            $tripIds = TripVisibility::visibleTripsQuery($user)->pluck('id');
            $q->whereIn('trip_id', $tripIds);
        }

        // Status filter — skip "estimate" pseudo-status
        if (! empty($filters['status']) && $filters['status'] !== 'estimate') {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['trip_id'])) {
            $q->where('trip_id', (int) $filters['trip_id']);
        }

        if (! empty($filters['trip_type'])) {
            $q->whereHas(
                'trip.dispatchRequest',
                fn (Builder $dr) => $dr->where('trip_type', $filters['trip_type']),
            );
        }

        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['fleet_mode'])) {
            $q->whereHas('trip', function (Builder $t) use ($filters) {
                match ($filters['fleet_mode']) {
                    'internal' => $t->whereNull('transport_provider_id')->whereNotNull('vehicle_id'),
                    'vendor_hire' => $t->whereNotNull('transport_provider_id')
                        ->whereHas('transportProvider', fn (Builder $p) => $p->where(
                            fn (Builder $i) => $i->whereNull('type')->orWhere('type', '!=', 'taxi'),
                        )),
                    'taxi' => $t->whereNotNull('transport_provider_id')
                        ->whereHas('transportProvider', fn (Builder $p) => $p->where('type', 'taxi')),
                    'unspecified' => $t->whereNull('transport_provider_id')->whereNull('vehicle_id'),
                    default => null,
                };
            });
        }

        return $q->limit(2000)->get()->map(function (TripCost $cost) {
            $trip = $cost->trip;
            $dr = $trip?->dispatchRequest;

            $tripType = $dr?->trip_type;
            $origin = $dr?->origin ?? '';
            $dest = $dr?->destination ?? '';
            $desc = trim((string) ($cost->description ?? ''));
            $content = $desc !== '' ? $desc : ($origin !== '' ? "{$origin} → {$dest}" : '—');

            $departAt = $trip?->depart_at;
            $monthKey = $departAt ? $departAt->format('Y-m') : ($cost->created_at ? $cost->created_at->format('Y-m') : '');

            $legKey = trim((string) ($cost->leg_key ?? ''));
            $legInfo = $legKey !== '' ? ($this->legMapForTrip($trip)[$legKey] ?? null) : null;

            return [
                'source' => 'recorded',
                'id' => $cost->id,
                'trip_id' => $cost->trip_id,
                'leg_key' => $legInfo ? $legKey : null,
                'leg_label' => $legInfo ? $this->legLabel($legInfo['seq']) : null,
                'leg_route' => $legInfo['route'] ?? null,
                'unit' => $dr?->requester?->department?->name ?? '—',
                'category' => $tripType,
                'submitter' => $this->submitterLabel($cost, $dr),
                'description' => $content,
                'fleet_source' => $this->fleetSource($trip),
                'provider' => $trip?->transportProvider?->name ?? '',
                'unit_price' => null,
                'extra_fee' => null,
                'amount' => (float) $cost->amount,
                'status' => $cost->status,
                'status_label' => self::STATUS_LABELS[$cost->status] ?? $cost->status,
                'sort_date' => ($cost->created_at ?? now())->toIso8601String(),
                'month_key' => $monthKey,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function estimatedRows(User $user, array $filters): array
    {
        // Only show estimates if the filter doesn't exclude them
        if (! empty($filters['status']) && $filters['status'] !== 'submitted' && $filters['status'] !== 'estimate') {
            return [];
        }
        if (! empty($filters['type']) && $filters['type'] !== 'wizard_estimate') {
            return [];
        }

        $wizFilters = array_filter([
            'trip_id' => $filters['trip_id'] ?? null,
            'trip_type' => $filters['trip_type'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'fleet_mode' => $filters['fleet_mode'] ?? null,
        ]);

        $lines = $this->wizardQuery->linesFor($user, $wizFilters);

        return array_map(function (array $line) {
            $trip = $line['trip'] ?? [];
            $dr = $trip['dispatch_request'] ?? [];
            $tripType = $dr['trip_type'] ?? null;
            $origin = $dr['origin'] ?? '';
            $dest = $dr['destination'] ?? '';
            $desc = trim((string) ($line['description'] ?? ''));
            $content = $desc !== '' ? $desc : ($origin !== '' ? "{$origin} → {$dest}" : '—');

            $sortDate = $line['created_at'] ?? now()->toIso8601String();
            $monthKey = '';
            try {
                $monthKey = Carbon::parse($sortDate)->format('Y-m');
            } catch (\Exception) {
            }

            // Fleet source from plain array (not Eloquent model)
            $hasProvider = ! empty($trip['transport_provider_id']);
            $hasVehicle = ! empty($trip['vehicle_id']);
            $providerType = $trip['transport_provider']['type'] ?? null;

            if ($hasProvider) {
                $providerName = $trip['transport_provider']['name'] ?? 'NCC';
                $fleetSource = $providerType === 'taxi' ? "Taxi ({$providerName})" : "Thuê xe ({$providerName})";
            } elseif ($hasVehicle) {
                $plate = $trip['vehicle']['license_plate'] ?? '';
                $fleetSource = $plate ? "Xe nội bộ ({$plate})" : 'Xe nội bộ';
            } else {
                $fleetSource = '—';
            }

            $legSeq = isset($line['leg_seq']) ? (int) $line['leg_seq'] : 0;

            return [
                'source' => 'estimate',
                'id' => "wiz-{$line['trip_id']}-{$line['line_key']}",
                'trip_id' => $line['trip_id'],
                'leg_key' => $line['leg_key'] ?? null,
                'leg_label' => $legSeq > 0 ? $this->legLabel($legSeq) : null,
                'leg_route' => null,
                'unit' => '—',
                'category' => $tripType,
                'submitter' => $line['requester_name'] ?? '—',
                'description' => $content,
                'fleet_source' => $fleetSource,
                'provider' => $trip['transport_provider']['name'] ?? '',
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'extra_fee' => (float) ($line['extra_fee'] ?? 0),
                'amount' => (float) ($line['amount'] ?? 0),
                'status' => 'estimate',
                'status_label' => 'Ước tính phiếu',
                'sort_date' => $sortDate,
                'month_key' => $monthKey,
            ];
        }, $lines);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Row helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function submitterLabel(TripCost $cost, mixed $dr): string
    {
        $requester = $dr?->requester?->name;
        if ($dr?->trip_type === 'business' && $requester) {
            return $requester;
        }

        return $cost->creator?->name ?? '—';
    }

    private function fleetSource(mixed $trip): string
    {
        if ($trip === null) {
            return '—';
        }
        $providerId = $trip->transport_provider_id;
        $vehicleId = $trip->vehicle_id;
        $providerName = $trip->transportProvider?->name ?? '';
        $providerType = $trip->transportProvider?->type ?? null;
        $plate = $trip->vehicle?->license_plate ?? '';

        if ($providerId) {
            return $providerType === 'taxi'
                ? "Taxi ({$providerName})"
                : "Thuê xe ({$providerName})";
        }
        if ($vehicleId) {
            return $plate ? "Xe nội bộ ({$plate})" : 'Xe nội bộ';
        }

        return '—';
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Statistics formatters
    // ──────────────────────────────────────────────────────────────────────────

    /** @param  array<string, float>  $map */
    private function pairsFromMap(array $map, array $labels): array
    {
        $out = [];
        foreach ($map as $key => $amount) {
            $out[] = ['key' => $key, 'label' => $labels[$key] ?? $key, 'amount' => $amount];
        }

        return $out;
    }

    /** @param  array<string, float>  $map */
    private function numericPairs(array $map): array
    {
        $out = [];
        foreach ($map as $key => $amount) {
            $out[] = ['label' => $key, 'amount' => $amount];
        }

        return $out;
    }

    /** @param  array<string, float>  $map  (sorted by month key) */
    private function monthPairs(array $map): array
    {
        $out = [];
        foreach ($map as $key => $amount) {
            try {
                $label = Carbon::createFromFormat('Y-m', $key)?->translatedFormat('M Y') ?? $key;
            } catch (\Exception) {
                $label = $key;
            }
            $out[] = ['key' => $key, 'label' => $label, 'amount' => $amount];
        }

        return $out;
    }
}
