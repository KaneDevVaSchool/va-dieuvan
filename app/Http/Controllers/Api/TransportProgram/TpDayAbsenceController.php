<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\MarkAbsentRequest;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpDayAbsenceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function store(MarkAbsentRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $data = $request->validated();
        $this->attendance->markAbsentBulk(
            $tpProgramDay,
            $data['student_ids'],
            $data['absence_type'],
            $data['absence_reason'] ?? null,
            $request->user()?->id,
            $data['category'] ?? null,
            $data['reason_code'] ?? null,
        );

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh()));
    }

    public function destroy(Request $request, TpProgramDay $tpProgramDay, int $student): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_attendance.manage')), 403);

        $this->attendance->unmarkAbsent($tpProgramDay, $student, $request->user()?->id);

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh()));
    }
}
