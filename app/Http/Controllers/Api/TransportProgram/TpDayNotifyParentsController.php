<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\NotifyDayParentsRequest;
use App\Http\Requests\Api\TransportProgram\PreviewDayParentsNotifyRequest;
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

    public function preview(PreviewDayParentsNotifyRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $v = $request->validated();
        $shift = isset($v['shift']) ? (string) $v['shift'] : null;

        return $this->ok($this->notify->preview(
            $tpProgramDay,
            $shift,
            (string) $v['scope'],
            array_map('intval', $v['student_ids'] ?? []),
        ));
    }

    public function store(NotifyDayParentsRequest $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_attendance.manage')), 403);

        $v = $request->validated();
        $shift = isset($v['shift']) ? (string) $v['shift'] : null;

        $scope = (string) ($v['scope'] ?? 'all_absent');

        $result = $this->notify->notifyAbsentParents(
            $tpProgramDay,
            $user->id,
            $shift,
            $scope,
            array_map('intval', $v['student_ids'] ?? []),
            isset($v['retry_batch_id']) ? (string) $v['retry_batch_id'] : null,
        );

        return $this->ok($result);
    }

    public function logs(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_attendance.manage')), 403);

        return $this->ok(['items' => $this->notify->listNotifyLogs($tpProgramDay)]);
    }
}
