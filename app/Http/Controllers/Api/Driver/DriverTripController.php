<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverTripHistoryRequest;
use App\Models\Trip;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DriverTripController extends Controller
{
    use ApiResponses;

    /** @var list<string> */
    private const PENDING_STATUSES = ['pending', 'assigned', 'driver_confirmed', 'approved'];

    public function history(DriverTripHistoryRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
        $perPage = min(15, max(1, $perPage));

        $baseStats = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest');

        $stats = $this->buildMonthlyStats(clone $baseStats);

        $q = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->with([
                'dispatchRequest:id,trip_type,origin,destination,passenger_count',
                'tripPassengers',
                'record:id,trip_id,distance_km',
            ]);

        $q->where('trips.depart_at', '>=', Carbon::parse($data['date_from'])->startOfDay())
            ->where('trips.depart_at', '<=', Carbon::parse($data['date_to'])->endOfDay());

        $status = isset($data['status']) ? (string) $data['status'] : null;
        if ($status !== null && $status !== '') {
            $this->applyStatusFilter($q, $status);
        }

        $q->orderByDesc('trips.depart_at')
            ->orderByDesc('trips.id');

        $results = $q->paginate($perPage);

        $items = collect($results->items())->map(fn (Trip $trip) => $this->serializeTrip($trip))->values()->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * @param  Builder<Trip>  $q
     */
    private function applyStatusFilter(Builder $q, string $status): void
    {
        match ($status) {
            'pending' => $q->whereIn('trips.status', self::PENDING_STATUSES),
            'in_progress', 'completed', 'cancelled' => $q->where('trips.status', $status),
            default => null,
        };
    }

    /**
     * @param  Builder<Trip>  $base
     * @return array{completed_this_month: int, cancelled_this_month: int, completed_growth_pct: ?int}
     */
    private function buildMonthlyStats(Builder $base): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $prevStart = $now->copy()->subMonth()->startOfMonth();
        $prevEnd = $now->copy()->subMonth()->endOfMonth();

        $completedThisMonth = (int) (clone $base)
            ->where('trips.status', 'completed')
            ->whereBetween('trips.depart_at', [$monthStart, $monthEnd])
            ->count();

        $cancelledThisMonth = (int) (clone $base)
            ->where('trips.status', 'cancelled')
            ->whereBetween('trips.depart_at', [$monthStart, $monthEnd])
            ->count();

        $completedLastMonth = (int) (clone $base)
            ->where('trips.status', 'completed')
            ->whereBetween('trips.depart_at', [$prevStart, $prevEnd])
            ->count();

        $growthPct = null;
        if ($completedLastMonth > 0) {
            $growthPct = (int) round((($completedThisMonth - $completedLastMonth) / $completedLastMonth) * 100);
        } elseif ($completedThisMonth > 0 && $completedLastMonth === 0) {
            $growthPct = 100;
        }

        return [
            'completed_this_month' => $completedThisMonth,
            'cancelled_this_month' => $cancelledThisMonth,
            'completed_growth_pct' => $growthPct,
        ];
    }

    private function serializeTrip(Trip $trip): array
    {
        $dr = $trip->dispatchRequest;
        $depart = $trip->depart_at;

        $durationMinutes = null;
        if ($trip->started_at && $trip->completed_at) {
            $seconds = abs($trip->started_at->diffInSeconds($trip->completed_at));
            $durationMinutes = (int) round($seconds / 60);
        }

        $passengerCount = (int) ($dr?->passenger_count ?? 0);
        if ($passengerCount <= 0 && $trip->relationLoaded('tripPassengers')) {
            $passengerCount = $trip->tripPassengers->count();
        }

        $distanceKm = $trip->record?->distance_km !== null
            ? (float) $trip->record->distance_km
            : null;

        return [
            'id' => $trip->id,
            'trip_number' => '#'.$trip->id,
            'type' => $this->tripTypeCode($dr?->trip_type),
            'status' => $trip->status,
            'depart_date' => $depart ? $depart->format('Y-m-d') : null,
            'pickup_time' => $depart ? $depart->format('H:i') : null,
            'pickup_date' => $depart ? $depart->format('d/m/Y') : null,
            'pickup_location' => $dr?->origin,
            'dropoff_location' => $dr?->destination,
            'passenger_count' => $passengerCount,
            'duration_minutes' => $durationMinutes,
            'distance_km' => $distanceKm,
        ];
    }

    private function tripTypeCode(?string $tripType): string
    {
        return match ($tripType) {
            'door_to_door' => 'D2D',
            'point_to_point' => 'P2P',
            'business' => 'CT',
            'cargo' => 'Cargo',
            default => '—',
        };
    }
}
