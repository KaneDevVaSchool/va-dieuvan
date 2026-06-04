<?php

namespace App\Services\TransportProgram;

use App\Actions\StartTripExecutionAction;
use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;

class TripExecutionService
{
    public function __construct(
        private readonly StartTripExecutionAction $startAction,
        private readonly TpAuditLogger $audit,
        private readonly StudentLogService $studentLogService,
    ) {}

    public function start(TpProgramDay $day, Driver $driver, ?string $deviceId = null): TpTripExecution
    {
        return $this->startAction->execute($day, $driver, $deviceId);
    }

    public function complete(TpTripExecution $execution, bool $confirmPendingBoard, ?int $actorId): TpTripExecution
    {
        $pending = $execution->studentLogs()->where('final_status', TpTripStudentLog::FINAL_PENDING)->count();
        abort_if($pending > 0, 422, 'Còn học sinh chưa xử lý.');

        $boarded = $execution->studentLogs()->where('final_status', TpTripStudentLog::FINAL_BOARDED)->count();
        if ($boarded > 0 && ! $confirmPendingBoard) {
            abort(422, 'Còn học sinh đã lên xe chưa xuống — cần xác nhận.');
        }

        if ($boarded > 0 && $confirmPendingBoard) {
            foreach ($execution->studentLogs()->where('final_status', TpTripStudentLog::FINAL_BOARDED)->get() as $log) {
                $this->studentLogService->alight($log, $actorId);
            }
        }

        $execution->update([
            'status' => TpTripExecution::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->audit->log($actorId, 'execution.completed', $execution, $execution->program);

        return $execution->fresh();
    }

    public function forceComplete(TpTripExecution $execution, ?int $actorId): TpTripExecution
    {
        foreach ($execution->studentLogs()->whereIn('final_status', [
            TpTripStudentLog::FINAL_PENDING,
            TpTripStudentLog::FINAL_BOARDED,
        ])->get() as $log) {
            if ($log->final_status === TpTripStudentLog::FINAL_BOARDED) {
                $this->studentLogService->alight($log, $actorId);
            } else {
                $this->studentLogService->markAbsent($log, 'no_notice', 'Force completed by dispatcher', $actorId);
            }
        }

        $execution->update([
            'status' => TpTripExecution::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->audit->log($actorId, 'execution.force_completed_by', $execution, $execution->program);

        return $execution->fresh();
    }

    public function cancel(TpTripExecution $execution, ?string $reason, ?int $actorId): TpTripExecution
    {
        $execution->update([
            'status' => TpTripExecution::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
        $this->audit->log($actorId, 'execution.cancelled', $execution, $execution->program);

        return $execution->fresh();
    }
}
