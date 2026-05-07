<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverContextSummaryRequest;
use App\Models\Driver;
use App\Models\TripCost;
use App\Models\Vehicle;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DriverContextController extends Controller
{
    use ApiResponses;

    public function summary(DriverContextSummaryRequest $request): JsonResponse
    {
        $user = $request->user();
        $driver = Driver::query()->where('user_id', $user->id)->first();

        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();
        $today = (int) (clone $this->newTripListBuilder($user))
            ->whereDate('trips.depart_at', now()->toDateString())
            ->count();
        $thisMonth = (int) (clone $this->newTripListBuilder($user))
            ->where('trips.depart_at', '>=', $monthStart)
            ->where('trips.depart_at', '<=', $monthEnd)
            ->count();

        $tripIds = $this->newTripListBuilder($user)->pluck('id');
        $pendingCosts = 0;
        if ($tripIds->isNotEmpty()) {
            $pendingCosts = (int) TripCost::query()
                ->whereIn('trip_id', $tripIds)
                ->where('status', 'submitted')
                ->count();
        }

        $vehicle = null;
        if ($driver) {
            $vehicle = Vehicle::query()
                ->where('default_driver_id', $driver->id)
                ->orderByDesc('id')
                ->first();
        }

        if (! $vehicle) {
            $tripWithVehicle = TripVisibility::visibleTripsQuery($user)
                ->whereHas('dispatchRequest')
                ->whereNotNull('vehicle_id')
                ->orderByDesc('trips.depart_at')
                ->with('vehicle')
                ->first();
            $vehicle = $tripWithVehicle?->vehicle;
        }

        $maintenance = $this->maintenanceSnapshot($vehicle);

        return $this->ok([
            'driver' => $driver ? [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'national_id' => $driver->national_id,
                'license_class' => $driver->license_class,
                'license_expires_at' => $driver->license_expires_at?->format('Y-m-d'),
            ] : null,
            'vehicle' => $this->serializeVehicle($vehicle),
            'stats' => [
                'trips_today' => $today,
                'trips_this_month' => $thisMonth,
                'pending_trip_costs' => $pendingCosts,
                'maintenance_due_soon' => $maintenance['due_soon_count'],
            ],
            'maintenance' => $maintenance,
        ]);
    }

    protected function newTripListBuilder($user): Builder
    {
        return TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest');
    }

    private function serializeVehicle(?Vehicle $v): ?array
    {
        if (! $v) {
            return null;
        }

        return [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'type' => $v->type,
            'seat_count' => (int) ($v->seat_count ?? 0),
            'status' => $v->status,
            'inspection_expires_at' => $v->inspection_expires_at?->format('Y-m-d'),
            'insurance_expires_at' => $v->insurance_expires_at?->format('Y-m-d'),
            'road_fee_expires_at' => $v->road_fee_expires_at?->format('Y-m-d'),
            'last_maintenance_at' => $v->last_maintenance_at?->format('Y-m-d'),
        ];
    }

    private function maintenanceSnapshot(?Vehicle $v): array
    {
        if (! $v) {
            return ['due_soon_count' => 0, 'items' => []];
        }

        $horizon = now()->addDays(90);
        $items = [];
        $map = [
            'inspection_expires_at' => 'inspection',
            'insurance_expires_at' => 'insurance',
            'road_fee_expires_at' => 'road_fee',
        ];

        foreach ($map as $attr => $key) {
            $d = $v->{$attr};
            if (! $d) {
                continue;
            }
            $c = $d instanceof \DateTimeInterface
                ? Carbon::instance($d)
                : Carbon::parse($d);
            if ($c->isFuture() && $c->lessThanOrEqualTo($horizon)) {
                $items[] = [
                    'key' => $key,
                    'date' => $c->format('Y-m-d'),
                    'days_left' => max(0, (int) now()->startOfDay()->diffInDays($c->copy()->startOfDay(), false)),
                ];
            }
        }

        return [
            'due_soon_count' => count($items),
            'items' => $items,
        ];
    }
}
