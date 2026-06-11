<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use App\Services\TransportProgram\StudentLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTpStudentActionController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly StudentLogService $studentLogService,
    ) {}

    public function board(Request $request, TpTripExecution $tpTripExecution, int $student): JsonResponse
    {
        $data = $request->validate([
            'client_timestamp' => ['nullable', 'date'],
        ]);
        $log = $this->resolveLog($request, $tpTripExecution, $student);
        $result = $this->studentLogService->board($log, $request->user()?->id, $data['client_timestamp'] ?? null);

        return $this->ok($this->logPayload($result));
    }

    public function alight(Request $request, TpTripExecution $tpTripExecution, int $student): JsonResponse
    {
        $data = $request->validate([
            'client_timestamp' => ['nullable', 'date'],
        ]);
        $log = $this->resolveLog($request, $tpTripExecution, $student);
        $result = $this->studentLogService->alight($log, $request->user()?->id, $data['client_timestamp'] ?? null);

        return $this->ok($this->logPayload($result));
    }

    public function absent(Request $request, TpTripExecution $tpTripExecution, int $student): JsonResponse
    {
        $data = $request->validate([
            'absence_type' => ['required', 'in:parent_notified,no_notice,late_cancel'],
            'notes' => ['nullable', 'string', 'max:500'],
            'client_timestamp' => ['nullable', 'date'],
        ]);
        $log = $this->resolveLog($request, $tpTripExecution, $student);
        $result = $this->studentLogService->markAbsent($log, $data['absence_type'], $data['notes'] ?? null, $request->user()?->id, $data['client_timestamp'] ?? null);

        return $this->ok($this->logPayload($result));
    }

    public function undoAbsent(Request $request, TpTripExecution $tpTripExecution, int $student): JsonResponse
    {
        $log = $this->resolveLog($request, $tpTripExecution, $student);
        $result = $this->studentLogService->undoAbsent($log, $request->user()?->id);

        return $this->ok($this->logPayload($result));
    }

    public function updateNotes(Request $request, TpTripExecution $tpTripExecution, int $student): JsonResponse
    {
        $data = $request->validate([
            'driver_notes' => ['nullable', 'string', 'max:500'],
        ]);
        $log = $this->resolveLog($request, $tpTripExecution, $student);
        $result = $this->studentLogService->updateDriverNotes(
            $log,
            $data['driver_notes'] ?? null,
            $request->user()?->id,
        );

        return $this->ok($this->logPayload($result));
    }

    private function resolveLog(Request $request, TpTripExecution $execution, int $studentId): TpTripStudentLog
    {
        $this->assertCanActOnExecution($request->user(), $execution);

        return TpTripStudentLog::query()
            ->where('execution_id', $execution->id)
            ->where('student_id', $studentId)
            ->firstOrFail();
    }

    private function logPayload(TpTripStudentLog $log): array
    {
        return [
            'id' => $log->id,
            'student_id' => $log->student_id,
            'final_status' => $log->final_status,
            'absence_type' => $log->absence_type,
            'sync_status' => $log->sync_status,
            'driver_notes' => $log->driver_notes,
        ];
    }
}
