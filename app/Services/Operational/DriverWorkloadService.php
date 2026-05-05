<?php

namespace App\Services\Operational;

use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DriverWorkloadService
{
    private const DEFAULT_WEEKLY_TARGET = 20;

    public function buildWorkloadMap(Carbon $from, Carbon $to, Carbon $tripDate): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $drivers = Driver::query()
            ->where('employment_status', 'active')
            ->where('availability_status', 'available')
            ->orderBy('full_name')
            ->get(['id']);

        $driverIds = $drivers->pluck('id');
        if ($driverIds->isEmpty()) {
            return [];
        }

        $tripsInRange = Trip::query()
            ->whereIn('driver_id', $driverIds)
            ->whereBetween('depart_at', [$fromStart, $toEnd])
            ->whereNotIn('status', ['cancelled'])
            ->get(['id', 'driver_id', 'depart_at', 'arrive_by']);

        $byDriver = $tripsInRange->groupBy('driver_id');

        $recentForResting = Trip::query()
            ->whereIn('driver_id', $driverIds)
            ->whereNotIn('status', ['cancelled'])
            ->where('depart_at', '>=', now()->subDays(3))
            ->get(['driver_id', 'depart_at', 'arrive_by']);

        $now = now();
        $cutoff = $now->copy()->subHour();
        $restingIds = $recentForResting
            ->filter(function (Trip $t) use ($now, $cutoff) {
                $end = $this->tripPlannedEnd($t);

                return $end->lessThanOrEqualTo($now) && $end->greaterThan($cutoff);
            })
            ->pluck('driver_id')
            ->unique()
            ->all();

        $restingSet = array_fill_keys($restingIds, true);

        $out = [];

        foreach ($drivers as $driver) {
            /** @var Collection<int, Trip> $trips */
            $trips = $byDriver->get($driver->id, collect());
            $tripsThisWeek = $trips->count();
            $tripsToday = $trips->filter(fn (Trip $t) => $t->depart_at instanceof Carbon
                ? $t->depart_at->isSameDay($tripDate)
                : Carbon::parse($t->depart_at)->isSameDay($tripDate))->count();

            $totalMinutes = $trips->sum(function (Trip $t) {
                $start = $t->depart_at instanceof Carbon ? $t->depart_at : Carbon::parse($t->depart_at);
                $end = $this->tripPlannedEnd($t);

                return max(0, $start->diffInMinutes($end));
            });

            $target = self::DEFAULT_WEEKLY_TARGET;
            $loadScore = min(100, (int) round($tripsThisWeek / max(1, $target) * 100));
            $loadLevel = match (true) {
                $loadScore >= 80 => 'high',
                $loadScore >= 50 => 'medium',
                default => 'low',
            };

            $lastTripEnd = $trips
                ->map(fn (Trip $t) => $this->tripPlannedEnd($t))
                ->sortDesc()
                ->first();

            $out[(string) $driver->id] = [
                'id' => $driver->id,
                'trips_this_week' => $tripsThisWeek,
                'trips_today' => $tripsToday,
                'total_hours' => round($totalMinutes / 60, 1),
                'load_score' => $loadScore,
                'load_level' => $loadLevel,
                'last_trip_end' => $lastTripEnd instanceof Carbon ? $lastTripEnd->toIso8601String() : null,
                'is_resting' => isset($restingSet[$driver->id]),
            ];
        }

        return $out;
    }

    /**
     * @return array{driver_id: int, chart: array<int, array{date: string, day_label: string, trip_count: int, hours: float}>, upcoming: array<int, array{id: int, scheduled_at: string, scheduled_end_at: string, trip_code: string}>}
     */
    public function buildWorkloadDetail(Driver $driver): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => now()->subDays($i)->format('Y-m-d'));

        $from = now()->subDays(6)->startOfDay();
        $trips = $driver->trips()
            ->where('depart_at', '>=', $from)
            ->whereNotIn('status', ['cancelled'])
            ->get(['id', 'depart_at', 'arrive_by']);

        $tripsGrouped = $trips->groupBy(fn (Trip $t) => ($t->depart_at instanceof Carbon
            ? $t->depart_at
            : Carbon::parse($t->depart_at))->format('Y-m-d'));

        $chart = $days->map(function (string $date) use ($tripsGrouped) {
            /** @var Collection<int, Trip> $dayTrips */
            $dayTrips = $tripsGrouped->get($date, collect());
            $minutes = $dayTrips->sum(function (Trip $t) {
                $start = $t->depart_at instanceof Carbon ? $t->depart_at : Carbon::parse($t->depart_at);

                return max(0, $start->diffInMinutes($this->tripPlannedEnd($t)));
            });

            return [
                'date' => $date,
                'day_label' => Carbon::parse($date)->locale('vi')->isoFormat('ddd'),
                'trip_count' => $dayTrips->count(),
                'hours' => round($minutes / 60, 1),
            ];
        })->values()->all();

        $upcoming = $driver->trips()
            ->where('depart_at', '>', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('depart_at')
            ->limit(3)
            ->get(['id', 'depart_at', 'arrive_by'])
            ->map(function (Trip $t) {
                $start = $t->depart_at instanceof Carbon ? $t->depart_at : Carbon::parse($t->depart_at);

                return [
                    'id' => $t->id,
                    'scheduled_at' => $start->toIso8601String(),
                    'scheduled_end_at' => $this->tripPlannedEnd($t)->toIso8601String(),
                    'trip_code' => 'TRP-'.str_pad((string) $t->id, 4, '0', STR_PAD_LEFT),
                ];
            })
            ->all();

        return [
            'driver_id' => $driver->id,
            'chart' => $chart,
            'upcoming' => $upcoming,
        ];
    }

    private function tripPlannedEnd(Trip $trip): Carbon
    {
        $depart = $trip->depart_at instanceof Carbon ? $trip->depart_at : Carbon::parse($trip->depart_at);
        if ($trip->arrive_by) {
            return $trip->arrive_by instanceof Carbon ? $trip->arrive_by : Carbon::parse($trip->arrive_by);
        }

        return $depart->copy()->addHours(2);
    }
}
