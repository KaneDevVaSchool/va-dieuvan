<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpProgramPresenter;
use App\Services\TransportProgram\TripExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTripStartController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly TripExecutionService $executionService,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function __invoke(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $driver = $this->assertCanStartDay($request->user(), $tpProgramDay);

        abort_unless($tpProgramDay->confirmed_at, 422, 'Bạn cần xác nhận chuyến trước khi bắt đầu.');

        $execution = $this->executionService->start($tpProgramDay, $driver, $request->input('device_id'));

        return $this->created($this->presenter->execution($execution));
    }
}
