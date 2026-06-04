<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpTripExecution;
use App\Services\TransportProgram\TpProgramPresenter;
use App\Services\TransportProgram\TripExecutionCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpExecutionCostController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TripExecutionCostService $costService,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function update(Request $request, TpTripExecution $tpTripExecution): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_cost.manage')), 403);

        $data = $request->validate([
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'cost_notes' => ['nullable', 'string'],
        ]);

        $execution = $this->costService->updateActualCost(
            $tpTripExecution,
            $data['actual_cost'] ?? null,
            $data['cost_notes'] ?? null,
            $user->id,
        );

        return $this->ok($this->presenter->execution($execution));
    }
}
