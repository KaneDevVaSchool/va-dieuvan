<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyTripSlotsRequest;
use App\Models\PolicyTripSlot;
use App\Services\P2pPolicy\PolicyTripSlotIndexQuery;
use App\Support\P2pPolicy;

class PolicyTripSlotController extends Controller
{
    use ApiResponses;

    public function index(ListPolicyTripSlotsRequest $request, PolicyTripSlotIndexQuery $queryBuilder)
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 25);
        $page = $queryBuilder->build($request)->paginate($perPage);

        $items = collect($page->items())->map(fn (PolicyTripSlot $slot) => $this->serializeSlot($slot))->values()->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSlot(PolicyTripSlot $slot): array
    {
        $route = $slot->policyRoute;
        $trip = $slot->trip;
        $dr = $slot->dispatchRequest ?? $trip?->dispatchRequest;

        $driver = $trip?->driver ?? $route?->driver;
        $vehicle = $trip?->vehicle ?? $route?->vehicle;

        $passengerCount = (int) ($dr?->passenger_count ?? 0);

        return [
            'id' => $slot->id,
            'p2p_policy_term_id' => $slot->p2p_policy_term_id,
            'policy_route_id' => $slot->policy_route_id,
            'run_date' => $slot->run_date?->toDateString(),
            'leg' => $slot->leg,
            'leg_label' => $slot->leg === P2pPolicy::LEG_AFTERNOON ? 'afternoon' : 'morning',
            'route_name' => $route?->name,
            'origin_campus' => $route?->originCampus ? [
                'id' => $route->originCampus->id,
                'code' => $route->originCampus->code,
                'name' => $route->originCampus->name,
            ] : null,
            'dest_campus' => $route?->destCampus ? [
                'id' => $route->destCampus->id,
                'code' => $route->destCampus->code,
                'name' => $route->destCampus->name,
            ] : null,
            'depart_at' => $trip?->depart_at?->toIso8601String(),
            'arrive_by' => $trip?->arrive_by?->toIso8601String(),
            'driver' => $driver ? [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
            ] : null,
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
            ] : null,
            'passenger_count' => $passengerCount,
            'trip_id' => $slot->trip_id,
            'trip_status' => $trip?->status,
            'dispatch_request_id' => $slot->dispatch_request_id,
            'depart_reminder_sent_at' => $slot->depart_reminder_sent_at?->toIso8601String(),
        ];
    }
}
