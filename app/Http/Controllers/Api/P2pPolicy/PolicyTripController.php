<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyTripsRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use Illuminate\Http\JsonResponse;

class PolicyTripController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripPresenter $presenter,
    ) {}

    /** Danh sách chuyến policy theo ngày cho dashboard điều vận (§8.1). */
    public function index(ListPolicyTripsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = PolicyTrip::query()
            ->with(['route', 'driver', 'vehicle'])
            ->forDate($data['date'])
            ->when($data['time_slot'] ?? null, fn ($q, $slot) => $q->where('time_slot', $slot))
            ->when($data['route_id'] ?? null, fn ($q, $routeId) => $q->where('route_id', $routeId))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('time_slot')
            ->orderBy('route_id')
            ->get()
            ->map(fn (PolicyTrip $t) => $this->presenter->tripRow($t))
            ->values()
            ->all();

        return $this->ok(['items' => $items]);
    }
}
