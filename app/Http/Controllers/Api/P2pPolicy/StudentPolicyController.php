<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListStudentPoliciesRequest;
use App\Http\Requests\Api\P2pPolicy\ManageStudentPolicyRequest;
use App\Http\Requests\Api\P2pPolicy\StoreStudentPolicyRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateStudentPolicyRequest;
use App\Models\StudentPolicy;
use App\Services\P2pPolicy\PolicyNotificationService;
use App\Services\P2pPolicy\PolicyTripPresenter;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

/**
 * Master list chính sách HS (§8.3). Soft-delete bằng deleted_at (§3.1). Đổi
 * trạng thái sang inactive/suspended sẽ đồng bộ các chuyến tương lai (§4.4).
 */
class StudentPolicyController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
        private readonly PolicyNotificationService $notifier,
    ) {}

    public function index(ListStudentPoliciesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = StudentPolicy::query()
            ->notDeleted()
            ->with(['student', 'route', 'pickupPoint', 'dropoffPoint'])
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['time_slot'] ?? null, fn ($q, $v) => $q->where('time_slot', $v))
            ->when($data['semester'] ?? null, fn ($q, $v) => $q->where('semester', (int) $v))
            ->when($data['route_id'] ?? null, fn ($q, $v) => $q->where('route_id', $v))
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentPolicy $sp) => $this->presenter->studentPolicyRow($sp))
            ->values()
            ->all();

        return $this->ok(['items' => $items]);
    }

    public function store(StoreStudentPolicyRequest $request): JsonResponse
    {
        $policy = StudentPolicy::create([
            ...$request->validated(),
            'semester' => (int) $request->validated('semester'),
            'status' => StudentPolicy::STATUS_ACTIVE,
            'created_by' => $request->user()?->id,
        ]);

        return $this->created($this->presenter->studentPolicyRow($policy));
    }

    public function update(UpdateStudentPolicyRequest $request, StudentPolicy $studentPolicy): JsonResponse
    {
        abort_if($studentPolicy->deleted_at !== null, 404);

        $data = $request->validated();
        if (isset($data['semester'])) {
            $data['semester'] = (int) $data['semester'];
        }

        $oldStatus = $studentPolicy->status;
        $studentPolicy->update($data);

        $affected = 0;
        $stoppingService = isset($data['status'])
            && in_array($data['status'], StudentPolicy::STATUSES_STOP_SERVICE, true)
            && $oldStatus === StudentPolicy::STATUS_ACTIVE;

        if ($stoppingService) {
            $affected = $this->policyTripService->syncStudentsOnPolicyStopService(
                $studentPolicy->fresh(),
                $request->user()?->id,
            );
            $this->notifier->policyChangeAffectsTrips($studentPolicy, $affected);
        }

        return $this->ok([
            ...$this->presenter->studentPolicyRow($studentPolicy->fresh()),
            'affected_trips' => $affected,
        ]);
    }

    /** Soft-delete (§10.1): deleted_at = NOW(), đồng bộ chuyến tương lai. */
    public function destroy(ManageStudentPolicyRequest $request, StudentPolicy $studentPolicy): JsonResponse
    {
        abort_if($studentPolicy->deleted_at !== null, 404);

        $affected = $this->policyTripService->syncStudentsOnPolicyStopService(
            $studentPolicy,
            $request->user()?->id,
        );

        $studentPolicy->update([
            'status' => StudentPolicy::STATUS_INACTIVE,
            'deleted_at' => now(),
        ]);

        $this->notifier->policyChangeAffectsTrips($studentPolicy, $affected);

        return $this->ok(['id' => $studentPolicy->id, 'affected_trips' => $affected]);
    }

    /** Số chuyến tương lai bị ảnh hưởng nếu ngừng policy — cảnh báo trước (§8.3). */
    public function impact(ManageStudentPolicyRequest $request, StudentPolicy $studentPolicy): JsonResponse
    {
        abort_if($studentPolicy->deleted_at !== null, 404);

        return $this->ok([
            'affected_trips' => $this->policyTripService->countAffectedUpcomingTrips($studentPolicy),
        ]);
    }
}
