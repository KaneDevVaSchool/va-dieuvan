<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\MarkPresentRequest;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\AttendanceService;
use Illuminate\Http\JsonResponse;

class TpDayPresentController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function store(MarkPresentRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $data = $request->validated();
        $actorId = $request->user()?->id;

        $shift = $data['shift'] ?? $request->query('shift');
        $shiftArg = is_string($shift) ? $shift : null;

        if (! empty($data['mark_all'])) {
            $this->attendance->markAllPresent($tpProgramDay, $actorId, $shiftArg);
        } else {
            foreach ($data['student_ids'] ?? [] as $studentId) {
                $this->attendance->markPresent($tpProgramDay, (int) $studentId, $actorId, $shiftArg);
            }
        }

        return $this->ok($this->attendance->getAttendance($tpProgramDay->fresh(), $shiftArg));
    }
}
