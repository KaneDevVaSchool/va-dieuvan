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
        $this->assertCanStartDay($request->user(), $tpProgramDay);

        $payload = $this->presenter->programDay($tpProgramDay);
        $payload['execution'] = $tpProgramDay->execution
            ? $this->presenter->execution($tpProgramDay->execution)
            : null;

        return $this->ok($payload);
    }
}
