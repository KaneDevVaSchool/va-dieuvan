<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\GeneratePolicyTripsRequest;
use App\Services\P2pPolicy\PolicyTripGeneratorService;
use Illuminate\Http\JsonResponse;

class PolicyTripGenerateController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripGeneratorService $generator,
    ) {}

    /** Điều vận sinh chuyến thủ công cho một ngày (§4). Idempotent. */
    public function __invoke(GeneratePolicyTripsRequest $request): JsonResponse
    {
        $result = $this->generator->generateForDate($request->validated('date'));

        if (in_array($result['result'], [
            PolicyTripGeneratorService::RESULT_NOT_SERVICE_DAY,
            PolicyTripGeneratorService::RESULT_MISSING_SEMESTER,
        ], true)) {
            return response()->json(['message' => $result['message'], 'data' => $result], 422);
        }

        return $this->ok($result, sprintf('Đã sinh %d chuyến mới, bỏ qua %d chuyến đã có.', $result['created'], $result['skipped']));
    }
}
