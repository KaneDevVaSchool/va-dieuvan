<?php

namespace App\Services\TransportProgram;

use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use Illuminate\Support\Collection;

class OfflineSyncService
{
    public function __construct(
        private readonly StudentLogService $studentLogService,
    ) {}

    /**
     * @param  array<int, array{action: string, student_id: int, client_timestamp?: string, absence_type?: string, notes?: string}>  $actions
     * @return array{applied: int, conflicts: array<int, array>}
     */
    public function processQueue(TpTripExecution $execution, array $actions, ?int $actorId): array
    {
        $sorted = collect($actions)->sortBy('client_timestamp')->values();
        $conflicts = [];
        $applied = 0;

        foreach ($sorted as $action) {
            $log = $execution->studentLogs()
                ->where('student_id', $action['student_id'])
                ->first();

            if (! $log) {
                $conflicts[] = ['student_id' => $action['student_id'], 'reason' => 'student_not_found'];

                continue;
            }

            $serverState = $log->final_status;
            $clientAction = $action['action'];

            try {
                match ($clientAction) {
                    'board' => $this->studentLogService->board($log, $actorId, $action['client_timestamp'] ?? null),
                    'alight' => $this->studentLogService->alight($log->fresh(), $actorId, $action['client_timestamp'] ?? null),
                    'absent' => $this->studentLogService->markAbsent(
                        $log->fresh(),
                        $action['absence_type'] ?? 'no_notice',
                        $action['notes'] ?? null,
                        $actorId,
                        $action['client_timestamp'] ?? null,
                    ),
                    default => null,
                };
                $applied++;
            } catch (\Throwable) {
                $conflicts[] = [
                    'student_id' => $action['student_id'],
                    'server_state' => $serverState,
                    'client_action' => $clientAction,
                ];
                $log->update(['sync_status' => 'conflict']);
            }
        }

        $execution->increment('sync_version');

        return ['applied' => $applied, 'conflicts' => $conflicts];
    }
}
