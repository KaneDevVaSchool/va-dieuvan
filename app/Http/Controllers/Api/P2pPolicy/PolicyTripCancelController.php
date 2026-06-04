<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\CancelPolicyTripRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripCancelController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function __invoke(CancelPolicyTripRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        if (in_array($policyTrip->status, ['completed', 'cancelled'], true)) {
            abort(422, 'Không thể hủy chuyến đã hoàn thành hoặc đã hủy.');
        }

        $data = $request->validated();
        $oldStatus = $policyTrip->status;

        $policyTrip->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
            'cancel_reason' => $data['reason'],
        ]);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'cancelled',
            ['status' => $oldStatus],
            ['status' => 'cancelled', 'reason' => $data['reason']],
        );

        $policyTrip->load(['route', 'driver', 'vehicle']);

        return $this->ok($this->policyTripService->tripToListArray($policyTrip));
    }
}
