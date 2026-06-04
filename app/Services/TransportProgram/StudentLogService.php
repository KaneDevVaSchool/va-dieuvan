<?php

namespace App\Services\TransportProgram;

use App\Models\TpDayAbsence;
use App\Models\TpTripStudentLog;
use Carbon\Carbon;

class StudentLogService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
        private readonly AttendanceService $attendance,
    ) {}

    public function board(TpTripStudentLog $log, ?int $actorId, ?string $clientTs = null): TpTripStudentLog
    {
        abort_unless(in_array($log->final_status, [TpTripStudentLog::FINAL_PENDING, TpTripStudentLog::FINAL_ABSENT], true), 422);

        $wasAbsent = $log->final_status === TpTripStudentLog::FINAL_ABSENT;

        $log->update([
            'final_status' => TpTripStudentLog::FINAL_BOARDED,
            'boarded_at' => now(),
            'boarded_by' => $actorId,
            'client_timestamp' => $this->parseClientTs($clientTs),
            'sync_status' => $this->syncStatusFromClient($clientTs),
        ]);

        if ($wasAbsent) {
            $log->execution->decrement('total_absent');
        }
        $log->execution->increment('total_boarded');

        return $log->fresh();
    }

    public function alight(TpTripStudentLog $log, ?int $actorId, ?string $clientTs = null): TpTripStudentLog
    {
        abort_unless($log->final_status === TpTripStudentLog::FINAL_BOARDED, 422);

        $log->update([
            'final_status' => TpTripStudentLog::FINAL_ALIGHTED,
            'alighted_at' => now(),
            'alighted_by' => $actorId,
            'client_timestamp' => $this->parseClientTs($clientTs),
        ]);
        $log->execution->increment('total_alighted');

        return $log->fresh();
    }

    public function markAbsent(TpTripStudentLog $log, string $type, ?string $notes, ?int $actorId, ?string $clientTs = null): TpTripStudentLog
    {
        abort_unless(in_array($log->final_status, [TpTripStudentLog::FINAL_PENDING, TpTripStudentLog::FINAL_BOARDED], true), 422);

        $wasPending = $log->final_status === TpTripStudentLog::FINAL_PENDING;

        $log->update([
            'final_status' => TpTripStudentLog::FINAL_ABSENT,
            'absence_type' => $type,
            'absence_notes' => $notes,
            'absent_at' => now(),
            'absent_by' => $actorId,
            'client_timestamp' => $this->parseClientTs($clientTs),
        ]);

        if ($wasPending) {
            $log->execution->increment('total_absent');
        }

        $day = $log->execution->programDay;
        TpDayAbsence::query()->updateOrCreate(
            ['program_day_id' => $day->id, 'student_id' => $log->student_id],
            [
                'absence_type' => $type === 'no_notice' ? 'no_notice' : ($type === 'late_cancel' ? 'late_cancel' : 'parent_notified'),
                'recorded_by' => $actorId,
                'recorded_at' => now(),
                'source' => 'driver',
            ]
        );

        return $log->fresh();
    }

    public function undoAbsent(TpTripStudentLog $log, ?int $actorId): TpTripStudentLog
    {
        abort_unless($log->final_status === TpTripStudentLog::FINAL_ABSENT, 422);
        abort_unless($log->execution->status === 'in_progress', 422);

        $log->update([
            'final_status' => TpTripStudentLog::FINAL_PENDING,
            'initial_status' => 'expected',
            'absent_at' => null,
            'absent_by' => null,
            'absence_type' => null,
        ]);
        $log->execution->decrement('total_absent');
        $this->attendance->unmarkAbsent($log->execution->programDay, $log->student_id, $actorId);
        $this->audit->log($actorId, 'log.absence_undone', $log, $log->execution->program);

        return $log->fresh();
    }

    private function parseClientTs(?string $clientTs): ?Carbon
    {
        return $clientTs ? Carbon::parse($clientTs) : null;
    }

    private function syncStatusFromClient(?string $clientTs): string
    {
        if (! $clientTs) {
            return 'synced';
        }
        $diff = abs(Carbon::parse($clientTs)->diffInMinutes(now()));

        return $diff > 30 ? 'timestamp_suspect' : 'synced';
    }
}
