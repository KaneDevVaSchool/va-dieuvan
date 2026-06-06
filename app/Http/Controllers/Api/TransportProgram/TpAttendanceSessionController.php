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

        $shift = $request->input('shift') ?? $request->query('shift');
        $shiftArg = is_string($shift) ? $shift : null;

        $this->attendance->saveDraft($tpProgramDay, $user->id, $shiftArg);

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh(), $shiftArg));
    }

    public function confirm(ConfirmAttendanceRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $data = $request->validated();
        $shift = $data['shift'] ?? $request->query('shift');
        $shiftArg = is_string($shift) ? $shift : null;

        $this->attendance->confirmAttendance(
            $tpProgramDay,
            (int) $data['attendance_lock_version'],
            $request->user()?->id,
            $shiftArg,
        );

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh(), $shiftArg));
    }

    public function reopen(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('data.override_confirmed')), 403);

        $shift = $request->input('shift') ?? $request->query('shift');
        $shiftArg = is_string($shift) ? $shift : null;

        $this->attendance->reopenAttendance($tpProgramDay, $user->id, $shiftArg);

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh(), $shiftArg));
    }
}
