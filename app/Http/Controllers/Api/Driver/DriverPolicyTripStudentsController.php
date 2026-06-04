<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverPolicyTripActionRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use Illuminate\Http\JsonResponse;

/** Danh sách HS để tài xế điểm danh (§5.1, §10.2). */
class DriverPolicyTripStudentsController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public function index(DriverPolicyTripActionRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        $this->assertCanActOnTrip($request->user(), $policyTrip);

        $items = $policyTrip->students()
            ->where('expected', true)
            ->with(['student', 'reporter'])
            ->orderBy('id')
            ->get()
            ->map(fn ($entry) => $this->presenter->studentRow($entry))
            ->values()
            ->all();

        return $this->ok([
            'trip' => $this->presenter->tripRow($policyTrip),
            'items' => $items,
        ]);
    }
}
