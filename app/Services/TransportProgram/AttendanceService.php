<?php

namespace App\Services\TransportProgram;

use App\Models\TpDayAbsence;
use App\Models\TpEnrollment;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';

    public function __construct(
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getAttendance(TpProgramDay $day): array
    {
        $day->loadMissing(['program', 'driver', 'program.defaultDriver']);

        $enrollments = $this->enrollmentsForDay($day);

        $absenceMap = TpDayAbsence::query()
            ->where('program_day_id', $day->id)
            ->get()
            ->keyBy('student_id');

        $items = $enrollments->map(function (TpEnrollment $e) use ($absenceMap) {
            $absence = $absenceMap->get($e->student_id);
            $status = $absence ? 'absent' : 'attending';
            $category = $absence?->category;

            return [
                'student_id' => $e->student_id,
                'code' => $e->student->code,
                'full_name' => $e->student->full_name,
                'grade' => $e->student->grade,
                'class_name' => $e->student->class_name,
                'parent_phone' => $e->student->parent_phone,
                'pickup_point' => $e->pickup_point,
                'status' => $status,
                'display_status' => $this->displayStatus($status, $category),
                'absence_type' => $absence?->absence_type,
                'category' => $category,
                'reason_code' => $absence?->reason_code,
                'absence_reason' => $absence?->absence_reason,
            ];
        })->values()->all();

        $summary = $this->buildSummary($items);

        return [
            'day' => [
                'id' => $day->id,
                'program_id' => $day->program_id,
                'scheduled_date' => $day->scheduled_date->toDateString(),
                'program_name' => $day->program?->name,
                'departure_time' => $day->program?->departure_time,
                'driver_name' => $day->effectiveDriver()?->full_name,
            ],
            'items' => $items,
            'summary' => $summary,
            'effective_count' => $summary['present'],
            'expected_count' => $day->expected_count,
            'attendance_status' => $day->attendance_status ?? self::STATUS_NOT_STARTED,
            'attendance_lock_version' => (int) ($day->attendance_lock_version ?? 0),
            'attendance_confirmed_at' => $day->attendance_confirmed_at?->toIso8601String(),
            'missing_reason_count' => $summary['missing_reason_count'],
        ];
    }

    public function markAbsent(
        TpProgramDay $day,
        int $studentId,
        string $type,
        ?string $reason,
        ?int $actorId,
        string $source = 'dispatcher',
        ?string $category = null,
        ?string $reasonCode = null,
        bool $syncExecution = true,
    ): void {
        DB::transaction(function () use ($day, $studentId, $type, $reason, $actorId, $source, $category, $reasonCode, $syncExecution) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);

            $existed = TpDayAbsence::query()
                ->where('program_day_id', $day->id)
                ->where('student_id', $studentId)
                ->exists();

            $resolvedCategory = $category ?? $this->categoryFromAbsenceType($type);

            TpDayAbsence::query()->updateOrCreate(
                ['program_day_id' => $day->id, 'student_id' => $studentId],
                [
                    'absence_type' => $type,
                    'category' => $resolvedCategory,
                    'reason_code' => $reasonCode,
                    'absence_reason' => $reason,
                    'recorded_by' => $actorId,
                    'recorded_at' => now(),
                    'source' => $source,
                ]
            );

            if (! $existed) {
                $day->decrement('expected_count');
            }

            if ($syncExecution) {
                $this->syncExecutionLogIfInProgress($day, $studentId, $type, $actorId);
            }

            $this->touchDraftStatus($day);

            $this->audit->log($actorId, 'absence.marked', $day, $day->program, metadata: [
                'student_id' => $studentId,
                'type' => $type,
                'source' => $source,
            ]);
        });
    }

    public function unmarkAbsent(TpProgramDay $day, int $studentId, ?int $actorId): void
    {
        DB::transaction(function () use ($day, $studentId, $actorId) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);

            $deleted = TpDayAbsence::query()
                ->where('program_day_id', $day->id)
                ->where('student_id', $studentId)
                ->delete();

            if ($deleted) {
                $day->increment('expected_count');
            }

            $execution = $day->execution()->where('status', TpTripExecution::STATUS_IN_PROGRESS)->first();
            if ($execution) {
                $log = TpTripStudentLog::query()
                    ->where('execution_id', $execution->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($log && $log->final_status === TpTripStudentLog::FINAL_ABSENT) {
                    $log->update([
                        'final_status' => TpTripStudentLog::FINAL_PENDING,
                        'initial_status' => 'expected',
                        'absent_at' => null,
                        'absent_by' => null,
                        'absence_type' => null,
                    ]);
                    $execution->decrement('total_absent');
                }
            }

            $this->touchDraftStatus($day);
            $this->audit->log($actorId, 'absence.unmarked', $day, $day->program, metadata: ['student_id' => $studentId]);
        });
    }

    public function markPresent(TpProgramDay $day, int $studentId, ?int $actorId): void
    {
        $this->unmarkAbsent($day, $studentId, $actorId);
    }

    /**
     * @param  array<int>  $studentIds
     */
    public function markAbsentBulk(
        TpProgramDay $day,
        array $studentIds,
        string $type,
        ?string $reason,
        ?int $actorId,
        ?string $category = null,
        ?string $reasonCode = null,
    ): void {
        foreach ($studentIds as $sid) {
            $this->markAbsent($day, (int) $sid, $type, $reason, $actorId, 'dispatcher', $category, $reasonCode);
        }
    }

    public function markAllPresent(TpProgramDay $day, ?int $actorId): void
    {
        $studentIds = TpDayAbsence::query()
            ->where('program_day_id', $day->id)
            ->pluck('student_id')
            ->all();

        foreach ($studentIds as $sid) {
            $this->unmarkAbsent($day, (int) $sid, $actorId);
        }
    }

    public function saveDraft(TpProgramDay $day, ?int $actorId): TpProgramDay
    {
        return DB::transaction(function () use ($day, $actorId) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            if ($day->attendance_status === self::STATUS_CONFIRMED) {
                abort(422, 'Điểm danh đã xác nhận, không thể lưu nháp.');
            }
            $day->update(['attendance_status' => self::STATUS_DRAFT]);
            $this->audit->log($actorId, 'attendance.draft_saved', $day, $day->program);

            return $day->fresh();
        });
    }

    public function confirmAttendance(TpProgramDay $day, int $expectedLockVersion, ?int $actorId): TpProgramDay
    {
        return DB::transaction(function () use ($day, $expectedLockVersion, $actorId) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);

            if ((int) $day->attendance_lock_version !== $expectedLockVersion) {
                abort(409, 'Phiên điểm danh đã được cập nhật bởi người khác. Vui lòng tải lại.');
            }

            $payload = $this->getAttendance($day);
            if ($payload['missing_reason_count'] > 0) {
                abort(422, 'Còn học sinh vắng chưa có lý do vắng.');
            }

            $updated = TpProgramDay::query()
                ->where('id', $day->id)
                ->where('attendance_lock_version', $expectedLockVersion)
                ->update([
                    'attendance_status' => self::STATUS_CONFIRMED,
                    'attendance_confirmed_at' => now(),
                    'attendance_confirmed_by' => $actorId,
                    'attendance_lock_version' => $expectedLockVersion + 1,
                ]);

            if ($updated !== 1) {
                abort(409, 'Phiên điểm danh đã được cập nhật bởi người khác. Vui lòng tải lại.');
            }

            $fresh = $day->fresh();
            $this->audit->log($actorId, 'attendance.confirmed', $fresh, $fresh->program);

            return $fresh;
        });
    }

    public function reopenAttendance(TpProgramDay $day, ?int $actorId): TpProgramDay
    {
        return DB::transaction(function () use ($day, $actorId) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->update([
                'attendance_status' => self::STATUS_DRAFT,
                'attendance_confirmed_at' => null,
                'attendance_confirmed_by' => null,
            ]);
            $this->audit->log($actorId, 'attendance.reopened', $day, $day->program);

            return $day->fresh();
        });
    }

    public function recalcExpectedCount(TpProgramDay $day): int
    {
        $enrolled = $this->enrollmentsForDay($day)->count();
        $absent = TpDayAbsence::query()->where('program_day_id', $day->id)->count();
        $effective = max(0, $enrolled - $absent);
        $day->update(['expected_count' => $effective]);

        return $effective;
    }

    /**
     * @return \Illuminate\Support\Collection<int, TpEnrollment>
     */
    private function enrollmentsForDay(TpProgramDay $day)
    {
        return TpEnrollment::query()
            ->with('student')
            ->where('program_id', $day->program_id)
            ->whereDate('enrolled_at', '<=', $day->scheduled_date)
            ->where(function ($q) use ($day) {
                $q->whereNull('unenrolled_at')
                    ->orWhereDate('unenrolled_at', '>', $day->scheduled_date);
            })
            ->orderBy('student_id')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int|float>
     */
    private function buildSummary(array $items): array
    {
        $total = count($items);
        $excused = 0;
        $unexcused = 0;
        $missingReason = 0;

        foreach ($items as $item) {
            if ($item['status'] !== 'absent') {
                continue;
            }
            if ($item['category'] === 'excused') {
                $excused++;
            } else {
                $unexcused++;
            }
            if (empty($item['reason_code'])) {
                $missingReason++;
            }
        }

        $absentTotal = $excused + $unexcused;
        $present = $total - $absentTotal;
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

        return [
            'present' => $present,
            'excused' => $excused,
            'unexcused' => $unexcused,
            'absent_total' => $absentTotal,
            'total' => $total,
            'attendance_rate' => $rate,
            'missing_reason_count' => $missingReason,
        ];
    }

    private function displayStatus(string $status, ?string $category): string
    {
        if ($status === 'attending') {
            return 'present';
        }

        return $category === 'excused' ? 'excused' : 'unexcused';
    }

    private function categoryFromAbsenceType(string $type): string
    {
        return in_array($type, ['parent_notified', 'late_cancel'], true) ? 'excused' : 'unexcused';
    }

    private function touchDraftStatus(TpProgramDay $day): void
    {
        if (($day->attendance_status ?? self::STATUS_NOT_STARTED) === self::STATUS_CONFIRMED) {
            return;
        }
        if ($day->attendance_status !== self::STATUS_DRAFT) {
            $day->update(['attendance_status' => self::STATUS_DRAFT]);
        }
    }

    private function syncExecutionLogIfInProgress(TpProgramDay $day, int $studentId, string $type, ?int $actorId): void
    {
        $execution = $day->execution()->where('status', TpTripExecution::STATUS_IN_PROGRESS)->first();
        if (! $execution) {
            return;
        }

        $log = TpTripStudentLog::query()
            ->where('execution_id', $execution->id)
            ->where('student_id', $studentId)
            ->first();

        if (! $log) {
            return;
        }

        $wasAbsent = $log->final_status === TpTripStudentLog::FINAL_ABSENT;
        $logAbsenceType = $this->executionAbsenceType($type);

        $log->update([
            'initial_status' => 'pre_absent',
            'final_status' => TpTripStudentLog::FINAL_ABSENT,
            'absence_type' => $logAbsenceType,
            'absent_at' => now(),
            'absent_by' => $actorId,
        ]);

        if (! $wasAbsent) {
            $execution->increment('total_absent');
        }
    }

    private function executionAbsenceType(string $type): string
    {
        if (in_array($type, ['parent_notified', 'no_notice', 'late_cancel'], true)) {
            return $type;
        }

        return 'no_notice';
    }
}
