<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\CancelPolicyTripRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripCancelController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
    ) {}

    /** Điều vận hủy chuyến thủ công + ghi lý do (§4.5, E4). Không tự hủy. */
    public function __invoke(CancelPolicyTripRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        if (in_array($policyTrip->status, [PolicyTrip::STATUS_COMPLETED, PolicyTrip::STATUS_CANCELLED], true)) {
            abort(422, 'Không thể hủy chuyến đã hoàn thành hoặc đã hủy.');
        }

        $reason = $request->validated()['reason'];
        $oldStatus = $policyTrip->status;

        $policyTrip->update([
            'status' => PolicyTrip::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
            'cancel_reason' => $reason,
        ]);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'cancelled',
            ['status' => $oldStatus],
            ['status' => PolicyTrip::STATUS_CANCELLED, 'reason' => $reason],
        );

        return $this->ok($this->presenter->tripRow($policyTrip->fresh(['route', 'driver', 'vehicle'])));
    }
}
