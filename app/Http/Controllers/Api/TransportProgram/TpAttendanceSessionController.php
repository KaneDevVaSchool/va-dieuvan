<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\ConfirmAttendanceRequest;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpAttendanceSessionController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function saveDraft(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_attendance.manage')), 403);

        $this->attendance->saveDraft($tpProgramDay, $user->id);

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh()));
    }

    public function confirm(ConfirmAttendanceRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $data = $request->validated();
        $this->attendance->confirmAttendance(
            $tpProgramDay,
            (int) $data['attendance_lock_version'],
            $request->user()?->id,
        );

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh()));
    }
}
