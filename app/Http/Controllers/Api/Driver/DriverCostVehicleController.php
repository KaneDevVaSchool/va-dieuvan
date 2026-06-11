<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverCostVehicleListRequest;
use App\Models\Vehicle;
use App\Support\DriverAssignableVehicles;
use Illuminate\Http\JsonResponse;

class DriverCostVehicleController extends Controller
{
    use ApiResponses;

    public function index(DriverCostVehicleListRequest $request): JsonResponse
    {
        $items = DriverAssignableVehicles::vehiclesForUser($request->user())
            ->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'license_plate' => $v->license_plate,
                'type' => $v->type,
                'seat_count' => (int) ($v->seat_count ?? 0),
                'status' => $v->status,
            ])
            ->values()
            ->all();

        return $this->ok(['items' => $items]);
    }
}
