<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpAttendanceParentNotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpDayNotifyParentsController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpAttendanceParentNotifyService $notify,
    ) {}

    public function store(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_attendance.manage')), 403);

        $result = $this->notify->notifyAbsentParents($tpProgramDay, $user->id);

        return $this->ok($result);
    }
}
