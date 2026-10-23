<?php

namespace App\Services\Resources;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\TripVisibility;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tình trạng giấy tờ cho tài xế tự xem (chỉ đọc): GPLX của tài xế + đăng kiểm / bảo hiểm / phí đường bộ /
 * bảo dưỡng của xe đang phụ trách. Cảnh báo cùng ngưỡng với phía điều phối:
 * giấy tờ hết hạn trong ComplianceDocumentService::EXPIRY_SOON_DAYS ngày, bảo dưỡng quá MAINTENANCE_STALE_DAYS ngày.
 */
class DriverDocumentStatusService
{
    public const MAINTENANCE_STALE_DAYS = 180;

    /** key => cột hạn trên bảng vehicles (đã đồng bộ từ kho chứng từ). */
    private const VEHICLE_EXPIRY_COLUMNS = [
        'inspection' => 'inspection_expires_at',
        'insurance' => 'insurance_expires_at',
        'road_fee' => 'road_fee_expires_at',
    ];

    public function __construct(private readonly ComplianceDocumentService $compliance) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $driver = Driver::query()->where('user_id', $user->id)->first();
        $vehicles = $this->vehiclesFor($user, $driver);

        $license = $driver ? $this->expiry($driver->license_expires_at) : null;
        $vehicleRows = $vehicles->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'type' => $v->type,
            'documents' => collect(self::VEHICLE_EXPIRY_COLUMNS)
                ->map(fn (string $column, string $key) => ['key' => $key, ...$this->expiry($v->{$column})])
                ->values()
                ->all(),
            'maintenance' => $this->maintenance($v->last_maintenance_at),
        ])->values()->all();

        return [
            'soon_days' => ComplianceDocumentService::EXPIRY_SOON_DAYS,
            'maintenance_stale_days' => self::MAINTENANCE_STALE_DAYS,
            'license' => $license,
            'vehicles' => $vehicleRows,
            'alerts' => $this->alerts($license, $vehicleRows),
        ];
    }

    /**
     * Xe được gán mặc định cho tài xế; chưa gán thì lấy xe của chuyến gần nhất.
     *
     * @return Collection<int, Vehicle>
     */
    private function vehiclesFor(User $user, ?Driver $driver): Collection
    {
        if ($driver !== null) {
            $assigned = Vehicle::query()
                ->where('default_driver_id', $driver->id)
                ->orderBy('license_plate')
                ->get();
            if ($assigned->isNotEmpty()) {
                return $assigned;
            }
        }

        $recent = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->whereNotNull('vehicle_id')
            ->orderByDesc('trips.depart_at')
            ->with('vehicle')
            ->first()?->vehicle;

        return collect($recent ? [$recent] : []);
    }

    /**
     * @return array{expires_at: string|null, state: string, days: int|null}
     */
    private function expiry(mixed $date): array
    {
        $state = $this->compliance->expiryState($date ? Carbon::parse($date) : null);

        return ['expires_at' => $state['until'], 'state' => $state['state'], 'days' => $state['days']];
    }

    /**
     * @return array{last_at: string|null, state: string, days_since: int|null}
     */
    private function maintenance(mixed $date): array
    {
        if (! $date) {
            return ['last_at' => null, 'state' => 'none', 'days_since' => null];
        }
        $last = Carbon::parse($date)->startOfDay();
        $since = (int) $last->diffInDays(Carbon::today(), false);

        return [
            'last_at' => $last->toDateString(),
            'state' => $since > self::MAINTENANCE_STALE_DAYS ? 'stale' : 'ok',
            'days_since' => max(0, $since),
        ];
    }

    /**
     * Danh sách cần chú ý: hết hạn trước, rồi sắp hết hạn (gần nhất trước), rồi bảo dưỡng quá hạn.
     *
     * @param  array<string, mixed>|null  $license
     * @param  list<array<string, mixed>>  $vehicles
     * @return list<array<string, mixed>>
     */
    private function alerts(?array $license, array $vehicles): array
    {
        $alerts = [];
        if ($license !== null && in_array($license['state'], ['exp', 'soon'], true)) {
            $alerts[] = ['key' => 'license', 'license_plate' => null, ...$license];
        }
        foreach ($vehicles as $v) {
            foreach ($v['documents'] as $doc) {
                if (in_array($doc['state'], ['exp', 'soon'], true)) {
                    $alerts[] = [...$doc, 'license_plate' => $v['license_plate']];
                }
            }
            if ($v['maintenance']['state'] === 'stale') {
                $alerts[] = [
                    'key' => 'maintenance',
                    'license_plate' => $v['license_plate'],
                    'state' => 'stale',
                    'expires_at' => $v['maintenance']['last_at'],
                    'days' => $v['maintenance']['days_since'],
                ];
            }
        }

        $rank = ['exp' => 0, 'soon' => 1, 'stale' => 2];
        usort($alerts, fn ($a, $b) => [$rank[$a['state']], $a['state'] === 'soon' ? $a['days'] : 0]
            <=> [$rank[$b['state']], $b['state'] === 'soon' ? $b['days'] : 0]);

        return $alerts;
    }
}
