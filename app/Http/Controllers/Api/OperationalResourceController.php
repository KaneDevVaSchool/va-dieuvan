<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\ListDriversRequest;
use App\Http\Requests\Api\Operational\ListVehiclesRequest;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class OperationalResourceController extends Controller
{
    use ApiResponses;

    public function vehicles(ListVehiclesRequest $request)
    {
        $data = $request->validated();

        $q = Vehicle::query()->orderBy('license_plate');
        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));

        $perPage = (int) ($data['per_page'] ?? 100);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function drivers(ListDriversRequest $request)
    {
        $data = $request->validated();

        $q = Driver::query()->orderBy('full_name');
        $q->when(
            isset($data['employment_status']),
            fn (Builder $b) => $b->where('employment_status', $data['employment_status']),
        );
        $q->when(
            isset($data['availability_status']),
            fn (Builder $b) => $b->where('availability_status', $data['availability_status']),
        );

        $perPage = (int) ($data['per_page'] ?? 100);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }
}
