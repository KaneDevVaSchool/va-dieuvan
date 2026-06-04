<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyTripStudentsRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Http\JsonResponse;

class PolicyTripStudentListController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    public function index(ListPolicyTripStudentsRequest $request, PolicyTrip $policyTrip): JsonResponse
    {
        $entries = $policyTrip->students()
            ->with(['student', 'reporter'])
            ->orderBy('id')
            ->get()
            ->map(fn ($e) => $this->policyTripService->studentEntryToArray($e))
            ->values()
            ->all();

        return $this->ok(['items' => $entries]);
    }
}
