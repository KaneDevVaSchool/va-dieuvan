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

    public function __invoke(GeneratePolicyTripsRequest $request): JsonResponse
    {
        $date = $request->validated('date');
        $result = $this->generator->generateForDate($date);

        if (! empty($result['message']) && $result['created'] === 0 && $result['skipped'] === 0) {
            return response()->json([
                'message' => $result['message'],
                'data' => $result,
            ], 422);
        }

        return $this->ok($result);
    }
}
