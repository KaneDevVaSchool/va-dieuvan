<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\MarkPolicyTripStudentAbsentRequest;
use App\Models\PolicyTripStudent;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripStudentAbsentController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function __invoke(
        MarkPolicyTripStudentAbsentRequest $request,
        PolicyTripStudent $policyTripStudent,
    ): JsonResponse {
        $policyTripStudent->load('policyTrip');
        $trip = $policyTripStudent->policyTrip;

        if (! in_array($trip->status, ['scheduled', 'assigned', 'in_progress'], true)) {
            abort(422, 'Không thể đánh dấu vắng cho chuyến ở trạng thái hiện tại.');
        }

        if ($policyTripStudent->boarded_at) {
            abort(422, 'Học sinh đã lên xe, không thể đánh dấu vắng.');
        }

        $data = $request->validated();

        $policyTripStudent->update([
            'absence_reason' => $data['absence_reason'],
            'reported_by' => $request->user()?->id,
        ]);

        $this->policyTripService->refreshTripCounts($trip);

        $this->policyTripService->recordAudit(
            $trip,
            $request->user()?->id,
            'student_marked_absent',
            null,
            [
                'policy_trip_student_id' => $policyTripStudent->id,
                'absence_reason' => $data['absence_reason'],
            ],
        );

        $policyTripStudent->load(['student', 'reporter']);

        return $this->ok($this->policyTripService->studentEntryToArray($policyTripStudent));
    }
}
