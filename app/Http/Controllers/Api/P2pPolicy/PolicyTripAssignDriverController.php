<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\AssignPolicyTripDriverRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyNotificationService;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripAssignDriverController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
        private readonly PolicyNotificationService $notifier,
    ) {}

    /** Điều vận gán tài xế/xe cho chuyến (§6). Block khi tài xế trùng ca (E2). */
    public function __invoke(AssignPolicyTripDriverRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        if (! $policyTrip->isPending()) {
            abort(422, 'Chỉ gán tài xế khi chuyến ở trạng thái chờ gán hoặc đã phân công.');
        }

        $data = $request->validated();
        $driverId = (int) $data['driver_id'];
        $tripDate = $policyTrip->trip_date->format('Y-m-d');

        // E2: một tài xế không được giao 2 chuyến cùng khung giờ + ngày.
        $conflict = $this->policyTripService->conflictingTrip($driverId, $tripDate, $policyTrip->time_slot, $policyTrip->id);
        if ($conflict) {
            $routeName = $conflict->route?->name ?? ('#'.$conflict->id);
            abort(409, "Tài xế đã có chuyến khác trong khung giờ này (tuyến {$routeName}). Vui lòng chọn tài xế khác.");
        }

        $old = $policyTrip->only(['driver_id', 'vehicle_id', 'status']);

        $policyTrip->update([
            'driver_id' => $driverId,
            'vehicle_id' => (int) $data['vehicle_id'],
            'status' => PolicyTrip::STATUS_ASSIGNED,
        ]);

        $policyTrip->load(['route', 'driver', 'vehicle']);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'assigned_driver',
            $old,
            $policyTrip->only(['driver_id', 'vehicle_id', 'status']),
        );

        // §6.3 / §11: thông báo tài xế được phân công (push + in-app).
        $this->notifier->tripDriverAssigned($policyTrip);

        return $this->ok($this->presenter->tripRow($policyTrip));
    }
}
