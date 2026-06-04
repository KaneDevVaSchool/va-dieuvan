<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\MarkPolicyTripStudentAbsentRequest;
use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Services\P2pPolicy\PolicyNotificationService;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

/**
 * Đánh dấu HS vắng (§7). Dùng chung 3 kênh:
 *  - Điều vận báo trước: absent_reported (Kênh 1)
 *  - Tài xế phát hiện tại chỗ: absent_no_notice (Kênh 2) → cảnh báo điều vận
 *  - Hủy muộn: late_cancellation (Kênh 3)
 * `expected` giữ nguyên true, absent_count tăng (§7.1).
 */
class PolicyTripStudentAbsentController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
        private readonly PolicyNotificationService $notifier,
    ) {}

    public function __invoke(
        MarkPolicyTripStudentAbsentRequest $request,
        PolicyTripStudent $policyTripStudent,
    ): JsonResponse {
        $policyTripStudent->loadMissing(['policyTrip', 'student']);
        $trip = $policyTripStudent->policyTrip;

        $this->assertCanActOnTrip($request->user(), $trip);

        if (! in_array($trip->status, [
            PolicyTrip::STATUS_SCHEDULED,
            PolicyTrip::STATUS_ASSIGNED,
            PolicyTrip::STATUS_IN_PROGRESS,
        ], true)) {
            abort(422, 'Không thể đánh dấu vắng cho chuyến ở trạng thái hiện tại.');
        }

        if ($policyTripStudent->boarded_at) {
            abort(422, 'Học sinh đã lên xe, không thể đánh dấu vắng.');
        }

        $reason = $request->validated()['absence_reason'];

        $policyTripStudent->update([
            'absence_reason' => $reason,
            'reported_by' => $request->user()?->id,
        ]);

        $this->policyTripService->refreshCounts($trip);
        $this->policyTripService->recordAudit($trip, $request->user()?->id, 'student_marked_absent', null, [
            'policy_trip_student_id' => $policyTripStudent->id,
            'absence_reason' => $reason,
        ]);

        // §7.1 Kênh 2: tài xế báo vắng không phép → điều vận realtime.
        if ($reason === PolicyTripStudent::ABSENCE_NO_NOTICE) {
            $this->notifier->studentAbsentNoNotice($trip, $policyTripStudent->student?->full_name ?? ('HS #'.$policyTripStudent->student_id));
        }

        return $this->ok($this->presenter->studentRow($policyTripStudent->fresh(['student', 'reporter'])));
    }
}
