<?php

namespace App\Services\TransportProgram;

use App\Models\TpTripExecution;
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
        $log->loadMissing('execution');
        abort_unless(
            $log->execution->status === TpTripExecution::STATUS_IN_PROGRESS,
            422,
            'Chuyến không còn đang chạy — không thể điểm danh lên xe.',
        );

        if ($log->final_status === TpTripStudentLog::FINAL_BOARDED) {
            return $log->fresh();
        }

        abort_unless(
            in_array($log->final_status, [TpTripStudentLog::FINAL_PENDING, TpTripStudentLog::FINAL_ABSENT], true),
            422,
            $this->boardBlockedMessage($log->final_status),
        );

        $wasAbsent = $log->final_status === TpTripStudentLog::FINAL_ABSENT;

        $log->update([
            'final_status' => TpTripStudentLog::FINAL_BOARDED,
            'boarded_at' => now(),
            'boarded_by' => $actorId,
            'client_timestamp' => $this->parseClientTs($clientTs),
            'sync_status' => $this->syncStatusFromClient($clientTs),
        ]);

        if ($wasAbsent) {
            $execution = $log->execution->fresh();
            if ($execution->total_absent > 0) {
                $execution->decrement('total_absent');
            }
            $this->attendance->markPresent(
                $log->execution->programDay,
                $log->student_id,
                $actorId,
                $log->execution->shift,
            );
        }
        $log->execution->increment('total_boarded');

        return $log->fresh();
    }

    public function alight(TpTripStudentLog $log, ?int $actorId, ?string $clientTs = null): TpTripStudentLog
    {
        $log->loadMissing('execution');
        abort_unless(
            $log->execution->status === TpTripExecution::STATUS_IN_PROGRESS,
            422,
            'Chuyến không còn đang chạy — không thể điểm danh xuống xe.',
        );

        if ($log->final_status === TpTripStudentLog::FINAL_ALIGHTED) {
            return $log->fresh();
        }

        abort_unless(
            $log->final_status === TpTripStudentLog::FINAL_BOARDED,
            422,
            'Chỉ xuống xe khi học sinh đang ở trạng thái đã lên xe.',
        );

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
        $wasBoarded = $log->final_status === TpTripStudentLog::FINAL_BOARDED;

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
        } elseif ($wasBoarded) {
            $log->execution->decrement('total_boarded');
            $log->execution->increment('total_absent');
        }

        $day = $log->execution->programDay;
        $absenceType = $type === 'no_notice' ? 'no_notice' : ($type === 'late_cancel' ? 'late_cancel' : 'parent_notified');
        $category = in_array($type, ['parent_notified', 'late_cancel'], true) ? 'excused' : 'unexcused';
        $reasonCode = $type === 'no_notice' ? 'no_notice' : null;

        $this->attendance->markAbsent(
            $day,
            $log->student_id,
            $absenceType,
            $notes,
            $actorId,
            'driver',
            $category,
            $reasonCode,
            syncExecution: false,
            shift: $log->execution->shift,
        );

        return $log->fresh();
    }

    public function updateDriverNotes(TpTripStudentLog $log, ?string $notes, ?int $actorId): TpTripStudentLog
    {
        abort_unless($log->execution->status === 'in_progress', 422, 'Chỉ ghi chú khi chuyến đang chạy.');

        $before = ['driver_notes' => $log->driver_notes];
        $trimmed = $notes !== null ? trim($notes) : null;
        $trimmed = $trimmed === '' ? null : $trimmed;

        $log->update(['driver_notes' => $trimmed]);

        $this->audit->log(
            $actorId,
            'log.driver_notes_updated',
            $log,
            $log->execution->program,
            $before,
            ['driver_notes' => $trimmed],
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
        $execution = $log->execution->fresh();
        if ($execution->total_absent > 0) {
            $execution->decrement('total_absent');
        }
        $this->attendance->unmarkAbsent($log->execution->programDay, $log->student_id, $actorId, $log->execution->shift);
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

    private function boardBlockedMessage(string $status): string
    {
        return match ($status) {
            TpTripStudentLog::FINAL_BOARDED => 'Học sinh đã lên xe.',
            TpTripStudentLog::FINAL_ALIGHTED => 'Học sinh đã xuống xe, không thể lên xe lại trên chuyến này.',
            default => 'Không thể điểm danh lên xe ở trạng thái hiện tại.',
        };
    }
}
