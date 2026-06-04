<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverPolicyTripActionRequest;
use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

/** Tài xế tích "Đã xuống xe" (§5.1 bước 4, §10.2). */
class DriverPolicyTripStudentAlightController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public function __invoke(DriverPolicyTripActionRequest $request, PolicyTripStudent $policyTripStudent): JsonResponse
    {
        $policyTripStudent->loadMissing('policyTrip');
        $trip = $policyTripStudent->policyTrip;

        $this->assertCanActOnTrip($request->user(), $trip);

        if ($trip->status !== PolicyTrip::STATUS_IN_PROGRESS) {
            abort(422, 'Chỉ điểm danh khi chuyến đang chạy.');
        }
        if ($policyTripStudent->boarded_at === null) {
            abort(422, 'Học sinh chưa lên xe nên không thể tích xuống.');
        }

        if ($policyTripStudent->alighted_at === null) {
            $policyTripStudent->update([
                'alighted_at' => now(),
                'alighted_by' => $request->user()?->id,
            ]);
            $this->policyTripService->recordAudit($trip, $request->user()?->id, 'student_alighted', null, [
                'policy_trip_student_id' => $policyTripStudent->id,
            ]);
        }

        return $this->ok($this->presenter->studentRow($policyTripStudent->fresh(['student', 'reporter'])));
    }
}
