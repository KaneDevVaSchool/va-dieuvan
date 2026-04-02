<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\Dispatching\DispatchingService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function assign(Request $request, Trip $trip, DispatchingService $dispatchingService)
    {
        $data = $request->validate([
            'lock_version' => ['required', 'integer', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'transport_provider_id' => ['nullable', 'integer', 'min:1'],
            'external_vehicle_ref' => ['nullable', 'string', 'max:255'],
            'external_driver_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $dispatchingService->assignResources($trip, [
            ...$data,
            'actor_id' => $request->user()->id,
            'dispatcher_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $updated]);
    }
}

