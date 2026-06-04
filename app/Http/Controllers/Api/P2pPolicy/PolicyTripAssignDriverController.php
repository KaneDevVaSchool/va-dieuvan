<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\AssignPolicyTripDriverRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripAssignDriverController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function __invoke(AssignPolicyTripDriverRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        if (! in_array($policyTrip->status, ['scheduled', 'assigned'], true)) {
            abort(422, 'Chỉ gán tài xế khi chuyến ở trạng thái chờ gán hoặc đã phân công.');
        }

        $data = $request->validated();
        $driverId = (int) $data['driver_id'];
        $tripDate = $policyTrip->trip_date->format('Y-m-d');

        if ($this->policyTripService->driverHasConflict($driverId, $tripDate, $policyTrip->time_slot, $policyTrip->id)) {
            abort(409, 'Tài xế đã có chuyến khác trong khung giờ này (E2). Vui lòng chọn tài xế khác.');
        }

        $old = [
            'driver_id' => $policyTrip->driver_id,
            'vehicle_id' => $policyTrip->vehicle_id,
            'status' => $policyTrip->status,
        ];

        $policyTrip->update([
            'driver_id' => $driverId,
            'vehicle_id' => (int) $data['vehicle_id'],
            'status' => 'assigned',
        ]);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'assigned_driver',
            $old,
            [
                'driver_id' => $policyTrip->driver_id,
                'vehicle_id' => $policyTrip->vehicle_id,
                'status' => $policyTrip->status,
            ],
        );

        $policyTrip->load(['route', 'driver', 'vehicle']);

        return $this->ok($this->policyTripService->tripToListArray($policyTrip));
    }
}
