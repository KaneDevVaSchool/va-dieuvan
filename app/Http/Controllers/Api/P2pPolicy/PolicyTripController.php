<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyTripsRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function index(ListPolicyTripsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $q = PolicyTrip::query()
            ->with(['route', 'driver', 'vehicle'])
            ->whereDate('trip_date', $data['date']);

        if (! empty($data['time_slot'])) {
            $q->where('time_slot', $data['time_slot']);
        }
        if (! empty($data['route_id'])) {
            $q->where('route_id', $data['route_id']);
        }
        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        $q->orderBy('time_slot')->orderBy('route_id');

        $items = $q->get()->map(fn (PolicyTrip $t) => $this->policyTripService->tripToListArray($t))->values()->all();

        return $this->ok(['items' => $items]);
    }
}
