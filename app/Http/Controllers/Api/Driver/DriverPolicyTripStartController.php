<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverPolicyTripActionRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

/** Tài xế bấm "Bắt đầu chuyến" (§5.1 bước 2, §10.2). */
class DriverPolicyTripStartController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public function __invoke(DriverPolicyTripActionRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        $this->assertCanActOnTrip($request->user(), $policyTrip);

        if ($policyTrip->status !== PolicyTrip::STATUS_ASSIGNED) {
            abort(422, 'Chỉ bắt đầu được chuyến đã được phân công.');
        }

        $policyTrip->update([
            'status' => PolicyTrip::STATUS_IN_PROGRESS,
            'actual_departure' => now(),
        ]);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'status_changed',
            ['status' => PolicyTrip::STATUS_ASSIGNED],
            ['status' => PolicyTrip::STATUS_IN_PROGRESS],
        );

        return $this->ok($this->presenter->tripRow($policyTrip->fresh(['route', 'driver', 'vehicle'])));
    }
}
