<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\CompletePolicyTripRequest;
use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Services\P2pPolicy\PolicyNotificationService;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

/**
 * Tài xế bấm "Hoàn thành chuyến" (§5.1 bước 5, §5.2).
 * - BLOCK nếu còn HS chưa xử lý (chưa lên, chưa đánh vắng).
 * - CẢNH BÁO (cần confirm) nếu còn HS đã lên nhưng chưa tích xuống.
 */
class DriverPolicyTripCompleteController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
        private readonly PolicyNotificationService $notifier,
    ) {}

    public function __invoke(CompletePolicyTripRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        $this->assertCanActOnTrip($request->user(), $policyTrip);

        if ($policyTrip->status !== PolicyTrip::STATUS_IN_PROGRESS) {
            abort(422, 'Chỉ hoàn thành được chuyến đang chạy.');
        }

        $expected = $policyTrip->students()->where('expected', true)->with('student')->get();

        // BLOCK: còn HS chưa xử lý (không lên xe, không đánh vắng).
        $unhandled = $expected->reject(fn (PolicyTripStudent $s) => $s->isHandled())->values();
        if ($unhandled->isNotEmpty()) {
            return response()->json([
                'message' => 'Còn học sinh chưa được xử lý — vui lòng tích lên xe hoặc đánh vắng trước khi hoàn thành.',
                'data' => [
                    'code' => 'unhandled_students',
                    'students' => $unhandled->map(fn (PolicyTripStudent $s) => [
                        'id' => $s->id,
                        'student_name' => $s->student?->full_name,
                    ])->all(),
                ],
            ], 422);
        }

        // CẢNH BÁO: HS đã lên nhưng chưa tích xuống — cần confirm.
        $boardedNotAlighted = $expected->filter(
            fn (PolicyTripStudent $s) => $s->boarded_at !== null && $s->alighted_at === null,
        )->values();

        if ($boardedNotAlighted->isNotEmpty() && ! $request->boolean('confirm')) {
            return response()->json([
                'message' => 'Còn học sinh đã lên xe nhưng chưa tích xuống. Xác nhận để hoàn thành?',
                'data' => [
                    'code' => 'boarded_not_alighted',
                    'requires_confirm' => true,
                    'students' => $boardedNotAlighted->map(fn (PolicyTripStudent $s) => [
                        'id' => $s->id,
                        'student_name' => $s->student?->full_name,
                    ])->all(),
                ],
            ], 422);
        }

        $policyTrip->update([
            'status' => PolicyTrip::STATUS_COMPLETED,
            'actual_arrival' => now(),
        ]);

        $this->policyTripService->recordAudit(
            $policyTrip,
            $request->user()?->id,
            'status_changed',
            ['status' => PolicyTrip::STATUS_IN_PROGRESS],
            ['status' => PolicyTrip::STATUS_COMPLETED],
        );

        // §11: còn HS lên mà chưa xuống khi complete → cảnh báo điều vận.
        if ($boardedNotAlighted->isNotEmpty()) {
            $this->notifier->tripBoardedNotAlighted($policyTrip, $boardedNotAlighted->count());
        }

        return $this->ok($this->presenter->tripRow($policyTrip->fresh(['route', 'driver', 'vehicle'])));
    }
}
