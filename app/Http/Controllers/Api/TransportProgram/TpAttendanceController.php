<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpAttendanceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function show(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.view') || $user->can('tp_attendance.manage')), 403);

        return $this->ok($this->attendance->getAttendance($tpProgramDay, $request->query('shift')));
    }
}
