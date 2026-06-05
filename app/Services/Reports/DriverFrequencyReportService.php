<?php

namespace App\Services\Reports;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DriverFrequencyReportService
{
    /** @var list<string> */
    public const TRIP_TYPE_ORDER = ['point_to_point', 'business', 'door_to_door', 'cargo'];

    public const TRIP_TYPE_LABELS = TripCostReportService::TRIP_TYPE_LABELS;

    private const DRIVER_TRIP_THRESHOLD = 60;

    private const VEHICLE_TRIP_THRESHOLD = 60;

    private const ON_TIME_GRACE_MINUTES = 15;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(User $user, array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $quarter = isset($filters['quarter']) && $filters['quarter'] !== ''
            ? (string) $filters['quarter']
            : null;

        [$from, $to] = $this->periodRange($year, $quarter);
        [$yearFrom, $yearTo] = $this->periodRange($year, null);

        $trips = $this->tripsForPeriod($user, $from, $to, $filters);

        $prevYear = $year - 1;
        [$prevFrom, $prevTo] = $this->periodRange($prevYear, $quarter);
        $prevFilters = $filters;
        $prevFilters['year'] = $prevYear;
        $prevTrips = $this->tripsForPeriod($user, $prevFrom, $prevTo, $prevFilters);

        $drivers = $this->buildDriverRows($trips);
        $vehicles = $this->buildVehicleRows($trips);
        $kpi = $this->buildKpi($trips, $drivers, $vehicles);
        $quarterly = $this->buildQuarterlyCounts($user, $year, $filters);
        $monthly = $this->buildMonthlyCounts($user, $year, $filters);

        $totalPrev = $prevTrips->count();
        $totalCurrent = $trips->count();
        $tripsDeltaPct = $totalPrev > 0
            ? round(100 * ($totalCurrent - $totalPrev) / $totalPrev, 1)
            : null;

        return [
            'year'            => $year,
            'quarter'         => $quarter,
            'kpi'             => $kpi,
            'drivers'         => $drivers,
            'vehicles'        => $vehicles,
            'quarterly'       => $quarterly,
            'monthly'         => $monthly,
            'year_comparison' => [
                'previous_year'   => $prevYear,
                'trips_delta_pct' => $tripsDeltaPct,
            ],
            'filter_options'  => $this->filterOptions($user, $yearFrom, $yearTo),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Trip>
     */
    private function tripsForPeriod(User $user, Carbon $from, Carbon $to, array $filters): Collection
    {
        return $this->baseTripQuery($user, $from, $to, $filters)
            ->get([
                'trips.id',
                'trips.driver_id',
                'trips.vehicle_id',
                'trips.depart_at',
                'trips.arrive_by',
                'trips.completed_at',
                'trips.status',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Trip>
     */
    private function baseTripQuery(User $user, Carbon $from, Carbon $to, array $filters): Builder
    {
        $q = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->whereNotIn('trips.status', ['cancelled'])
            ->whereBetween('trips.depart_at', [$from, $to])
            ->with([
                'dispatchRequest:id,trip_type',
                'driver:id,full_name',
                'vehicle:id,license_plate',
            ]);

        if (! empty($filters['driver_id'])) {
            $q->where('trips.driver_id', (int) $filters['driver_id']);
        }

        if (! empty($filters['vehicle_id'])) {
            $q->where('trips.vehicle_id', (int) $filters['vehicle_id']);
        } elseif (! empty($filters['vehicle_plate'])) {
            $plate = (string) $filters['vehicle_plate'];
            $q->whereHas('vehicle', fn (Builder $v) => $v->where('license_plate', $plate));
        }

        if (! empty($filters['trip_type'])) {
            $type = (string) $filters['trip_type'];
            $q->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('trip_type', $type));
        }

        return $q;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodRange(int $year, ?string $quarter): array
    {
        if ($quarter !== null && $quarter !== '') {
            $months = match ($quarter) {
                'q1' => [1, 3],
                'q2' => [4, 6],
                'q3' => [7, 9],
                'q4' => [10, 12],
                default => [1, 12],
            };
            $from = Carbon::create($year, $months[0], 1)->startOfDay();
            $to = Carbon::create($year, $months[1], 1)->endOfMonth()->endOfDay();

            return [$from, $to];
        }

        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        return [$from, $to];
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @return list<array<string, mixed>>
     */
    private function buildDriverRows(Collection $trips): array
    {
        $byDriver = $trips->filter(fn (Trip $t) => $t->driver_id !== null)->groupBy('driver_id');
        $rows = [];

        foreach ($byDriver as $driverId => $driverTrips) {
            /** @var Trip $sample */
            $sample = $driverTrips->first();
            $driver = $sample->driver;
            if (! $driver) {
                continue;
            }

            $typeCounts = $this->typeCountsForTrips($driverTrips);
            $onTime = $this->onTimePercent($driverTrips);
            $hours = round($this->totalHours($driverTrips), 1);
            $tripCount = $driverTrips->count();

            $rows[] = [
                'id'     => (int) $driverId,
                'code'   => $this->driverCode($driver),
                'name'   => $driver->full_name ?? ('Tài xế #'.$driverId),
                'trips'  => $tripCount,
                'hours'  => $hours,
                'onTime' => $onTime,
                'types'  => array_values($typeCounts),
                'kpi'    => 0,
                'bonus'  => 'C',
            ];
        }

        usort($rows, fn ($a, $b) => $b['trips'] <=> $a['trips']);

        $maxTrips = $rows[0]['trips'] ?? 0;
        foreach ($rows as &$row) {
            $row['kpi'] = $this->computeKpiScore($row['trips'], $maxTrips, $row['onTime'], $row['types']);
            $row['bonus'] = $this->bonusTier($row['kpi']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @return list<array<string, mixed>>
     */
    private function buildVehicleRows(Collection $trips): array
    {
        $byVehicle = $trips->filter(fn (Trip $t) => $t->vehicle_id !== null)->groupBy('vehicle_id');
        $rows = [];

        foreach ($byVehicle as $vehicleId => $vehicleTrips) {
            /** @var Trip $sample */
            $sample = $vehicleTrips->first();
            $vehicle = $sample->vehicle;
            if (! $vehicle) {
                continue;
            }

            $rows[] = [
                'id'    => (int) $vehicleId,
                'plate' => (string) ($vehicle->license_plate ?? ('#'.$vehicleId)),
                'trips' => $vehicleTrips->count(),
            ];
        }

        usort($rows, fn ($a, $b) => $b['trips'] <=> $a['trips']);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $drivers
     * @param  list<array<string, mixed>>  $vehicles
     * @return array<string, mixed>
     */
    private function buildKpi(Collection $trips, array $drivers, array $vehicles): array
    {
        $totalTrips = $trips->count();
        $activeDrivers = count($drivers);
        $activeVehicles = count($vehicles);
        $totalHours = round($this->totalHours($trips), 1);
        $overallOnTime = $this->onTimePercent($trips);

        $avgTripsPerDriver = $activeDrivers > 0
            ? round($totalTrips / $activeDrivers, 1)
            : 0.0;
        $avgTripsPerVehicle = $activeVehicles > 0
            ? round($totalTrips / $activeVehicles, 1)
            : 0.0;
        $avgHoursPerDriver = $activeDrivers > 0
            ? round($totalHours / $activeDrivers, 1)
            : 0.0;

        $driversAboveThreshold = count(array_filter(
            $drivers,
            fn ($d) => ($d['trips'] ?? 0) >= self::DRIVER_TRIP_THRESHOLD,
        ));
        $vehiclesBelowThreshold = count(array_filter(
            $vehicles,
            fn ($v) => ($v['trips'] ?? 0) < self::VEHICLE_TRIP_THRESHOLD,
        ));

        return [
            'totalTrips'             => $totalTrips,
            'activeDrivers'          => $activeDrivers,
            'avgTripsPerDriver'      => $avgTripsPerDriver,
            'driversAboveThreshold'  => $driversAboveThreshold,
            'activeVehicles'         => $activeVehicles,
            'avgTripsPerVehicle'     => $avgTripsPerVehicle,
            'vehiclesBelowThreshold' => $vehiclesBelowThreshold,
            'totalHours'             => $totalHours,
            'avgHoursPerDriver'      => $avgHoursPerDriver,
            'overallOnTime'          => $overallOnTime,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function buildQuarterlyCounts(User $user, int $year, array $filters): array
    {
        $baseFilters = $filters;
        unset($baseFilters['quarter']);

        $counts = [];
        for ($q = 1; $q <= 4; $q++) {
            [$from, $to] = $this->periodRange($year, 'q'.$q);
            $counts[] = $this->baseTripQuery($user, $from, $to, $baseFilters)->count();
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function buildMonthlyCounts(User $user, int $year, array $filters): array
    {
        $baseFilters = $filters;
        unset($baseFilters['quarter']);

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $from = Carbon::create($year, $m, 1)->startOfDay();
            $to = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay();
            $monthly[] = $this->baseTripQuery($user, $from, $to, $baseFilters)->count();
        }

        return $monthly;
    }

    /**
     * @return array{drivers: list<array{id:int,name:string,code:string}>, vehicles: list<array{id:int,plate:string}>}
     */
    private function filterOptions(User $user, Carbon $from, Carbon $to): array
    {
        $trips = $this->baseTripQuery($user, $from, $to, [])->get(['trips.driver_id', 'trips.vehicle_id']);

        $driverIds = $trips->pluck('driver_id')->filter()->unique()->values();
        $vehicleIds = $trips->pluck('vehicle_id')->filter()->unique()->values();

        $drivers = Driver::query()
            ->whereIn('id', $driverIds)
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Driver $d) => [
                'id'   => $d->id,
                'name' => $d->full_name ?? ('#'.$d->id),
                'code' => $this->driverCode($d),
            ])
            ->values()
            ->all();

        $vehicles = Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->orderBy('license_plate')
            ->get(['id', 'license_plate'])
            ->map(fn ($v) => [
                'id'    => $v->id,
                'plate' => (string) ($v->license_plate ?? '#'.$v->id),
            ])
            ->values()
            ->all();

        return [
            'drivers'  => $drivers,
            'vehicles' => $vehicles,
        ];
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @return list<int>
     */
    private function typeCountsForTrips(Collection $trips): array
    {
        $counts = array_fill_keys(self::TRIP_TYPE_ORDER, 0);
        foreach ($trips as $trip) {
            $type = $trip->dispatchRequest?->trip_type;
            if ($type !== null && isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return array_values($counts);
    }

    /**
     * @param  Collection<int, Trip>  $trips
     */
    private function onTimePercent(Collection $trips): float
    {
        $eligible = 0;
        $onTime = 0;

        foreach ($trips as $trip) {
            if ($trip->status !== 'completed') {
                continue;
            }
            $deadline = $trip->arrive_by;
            $completed = $trip->completed_at;
            if (! $deadline || ! $completed) {
                continue;
            }
            $deadlineAt = $deadline instanceof Carbon ? $deadline : Carbon::parse($deadline);
            $completedAt = $completed instanceof Carbon ? $completed : Carbon::parse($completed);
            $eligible++;
            if ($completedAt->lessThanOrEqualTo($deadlineAt->copy()->addMinutes(self::ON_TIME_GRACE_MINUTES))) {
                $onTime++;
            }
        }

        if ($eligible === 0) {
            return 0.0;
        }

        return round(100 * $onTime / $eligible, 1);
    }

    /**
     * @param  Collection<int, Trip>  $trips
     */
    private function totalHours(Collection $trips): float
    {
        $minutes = $trips->sum(function (Trip $trip) {
            $start = $trip->depart_at instanceof Carbon
                ? $trip->depart_at
                : Carbon::parse($trip->depart_at);
            $end = $this->tripPlannedEnd($trip);

            return max(0, $start->diffInMinutes($end));
        });

        return $minutes / 60;
    }

    private function tripPlannedEnd(Trip $trip): Carbon
    {
        $depart = $trip->depart_at instanceof Carbon
            ? $trip->depart_at
            : Carbon::parse($trip->depart_at);
        if ($trip->arrive_by) {
            return $trip->arrive_by instanceof Carbon
                ? $trip->arrive_by
                : Carbon::parse($trip->arrive_by);
        }

        return $depart->copy()->addHours(2);
    }

    /** @param list<int> $typeCounts */
    private function computeKpiScore(int $trips, int $maxTrips, float $onTime, array $typeCounts): int
    {
        $freqScore = $maxTrips > 0 ? ($trips / $maxTrips) * 100 : 0.0;
        $diversityBuckets = count(array_filter($typeCounts, fn ($c) => $c > 0));
        $diversityScore = ($diversityBuckets / count(self::TRIP_TYPE_ORDER)) * 100;
        $score = 0.5 * $freqScore + 0.3 * $onTime + 0.2 * $diversityScore;

        return (int) round(min(100, max(0, $score)));
    }

    private function bonusTier(int $kpi): string
    {
        if ($kpi >= 88) {
            return 'A';
        }
        if ($kpi >= 70) {
            return 'B';
        }

        return 'C';
    }

    private function driverCode(Driver $driver): string
    {
        $name = trim((string) ($driver->full_name ?? ''));
        if ($name === '') {
            return 'D'.$driver->id;
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($letters) >= 3) {
                break;
            }
        }

        return $letters !== '' ? $letters : ('D'.$driver->id);
    }
}
