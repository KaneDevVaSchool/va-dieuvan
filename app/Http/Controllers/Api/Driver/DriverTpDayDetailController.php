<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTpDayDetailController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function show(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $shift = $request->query('shift');
        $shift = in_array($shift, ['morning', 'afternoon'], true) ? $shift : null;
        $this->assertCanStartDay($request->user(), $tpProgramDay, $shift);

        $tpProgramDay->loadMissing('executions');
        $payload = $this->presenter->programDay($tpProgramDay);
        $slotExecution = $tpProgramDay->executionForShift($shift);
        $payload['execution'] = $slotExecution
            ? $this->presenter->execution($slotExecution)
            : null;
        $payload['execution_status'] = $slotExecution?->status;
        $payload['shift'] = $shift;

        return $this->ok($payload);
    }
}
