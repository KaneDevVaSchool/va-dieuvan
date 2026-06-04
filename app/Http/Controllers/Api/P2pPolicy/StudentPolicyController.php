<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListStudentPoliciesRequest;
use App\Http\Requests\Api\P2pPolicy\StoreStudentPolicyRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateStudentPolicyRequest;
use App\Models\StudentPolicy;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;
class StudentPolicyController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function index(ListStudentPoliciesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $q = StudentPolicy::query()
            ->whereNull('deleted_at')
            ->with(['student', 'route', 'pickupPoint', 'dropoffPoint']);

        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (! empty($data['time_slot'])) {
            $q->where('time_slot', $data['time_slot']);
        }
        if (! empty($data['semester'])) {
            $q->where('semester', (int) $data['semester']);
        }
        if (! empty($data['route_id'])) {
            $q->where('route_id', $data['route_id']);
        }

        $items = $q->orderByDesc('id')->get()->map(fn (StudentPolicy $sp) => $this->toArray($sp))->values()->all();

        return $this->ok(['items' => $items]);
    }

    public function store(StoreStudentPolicyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $policy = StudentPolicy::create([
            ...$data,
            'semester' => (int) $data['semester'],
            'status' => 'active',
            'created_by' => $request->user()?->id,
        ]);

        $policy->load(['student', 'route', 'pickupPoint', 'dropoffPoint']);

        return $this->created($this->toArray($policy));
    }

    public function update(UpdateStudentPolicyRequest $request, StudentPolicy $studentPolicy): JsonResponse
    {
        abort_if($studentPolicy->deleted_at !== null, 404);

        $data = $request->validated();
        $oldStatus = $studentPolicy->status;

        if (isset($data['semester'])) {
            $data['semester'] = (int) $data['semester'];
        }

        $studentPolicy->update($data);

        if (
            isset($data['status'])
            && in_array($data['status'], ['inactive', 'suspended'], true)
            && $oldStatus === 'active'
        ) {
            $this->policyTripService->syncFutureTripsOnPolicySuspend(
                $studentPolicy->fresh(),
                (int) $request->user()?->id,
            );
        }

        $studentPolicy->load(['student', 'route', 'pickupPoint', 'dropoffPoint']);

        return $this->ok($this->toArray($studentPolicy));
    }

    private function toArray(StudentPolicy $sp): array
    {
        return [
            'id' => $sp->id,
            'student_id' => $sp->student_id,
            'student_name' => $sp->student?->full_name,
            'class_name' => $sp->student?->grade,
            'route_id' => $sp->route_id,
            'route_name' => $sp->route?->name,
            'school_year' => $sp->school_year,
            'semester' => $sp->semester,
            'time_slot' => $sp->time_slot,
            'pickup_point_id' => $sp->pickup_point_id,
            'dropoff_point_id' => $sp->dropoff_point_id,
            'pickup_point' => $sp->pickupPoint?->name ?? $sp->pickupPoint?->address,
            'dropoff_point' => $sp->dropoffPoint?->name ?? $sp->dropoffPoint?->address,
            'effective_from' => $sp->effective_from?->format('Y-m-d'),
            'effective_to' => $sp->effective_to?->format('Y-m-d'),
            'status' => $sp->status,
        ];
    }
}
