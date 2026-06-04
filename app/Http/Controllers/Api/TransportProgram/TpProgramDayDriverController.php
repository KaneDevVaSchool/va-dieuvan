<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\AssignDayDriverRequest;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\DriverAssignmentService;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpProgramDayDriverController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DriverAssignmentService $assignment,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function assign(AssignDayDriverRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $data = $request->validated();
        $this->assignment->assignDriver($tpProgramDay, $data['driver_id'], $data['vehicle_id'] ?? null, $request->user()?->id);

        return $this->ok($this->presenter->programDay($tpProgramDay->fresh()));
    }

    public function remove(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_driver_assign.manage')), 403);

        $this->assignment->clearOverride($tpProgramDay, $request->user()?->id);

        return $this->ok($this->presenter->programDay($tpProgramDay->fresh()));
    }
}
