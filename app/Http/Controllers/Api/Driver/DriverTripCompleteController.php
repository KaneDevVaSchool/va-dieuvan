<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpTripExecution;
use App\Services\TransportProgram\TpProgramPresenter;
use App\Services\TransportProgram\TripExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTripCompleteController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly TripExecutionService $executionService,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function complete(Request $request, TpTripExecution $tpTripExecution): JsonResponse
    {
        $this->assertCanActOnExecution($request->user(), $tpTripExecution);

        $confirm = (bool) $request->boolean('confirm_pending_board');
        $execution = $this->executionService->complete($tpTripExecution, $confirm, $request->user()?->id);

        return $this->ok($this->presenter->execution($execution));
    }

    public function forceComplete(Request $request, TpTripExecution $tpTripExecution): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_execution.force_complete')), 403);

        $execution = $this->executionService->forceComplete($tpTripExecution, $user->id);

        return $this->ok($this->presenter->execution($execution));
    }
}
