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
        private readonly TpAttendanceShiftResolver $shiftResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getAttendance(TpProgramDay $day, ?string $shift = null): array
    {
        $day->loadMissing(['program', 'driver', 'program.defaultDriver']);
        $program = $day->program;
        abort_if($program === null, 404);

        $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);
        $multiSlot = $this->shiftResolver->isMultiSlot($program);

        $enrollments = $this->enrollmentsForDay($day);

        $absenceQuery = TpDayAbsence::query()->where('program_day_id', $day->id);
        $this->shiftResolver->applyAbsenceScope($absenceQuery, $storageShift);
        $absenceMap = $absenceQuery->get()->keyBy('student_id');

        $boardedAtMap = $this->boardedAtMapForDay($day);

        $items = $enrollments->map(function (TpEnrollment $e) use ($absenceMap, $boardedAtMap) {
            $absence = $absenceMap->get($e->student_id);
            $status = $absence ? 'absent' : 'attending';
            $category = $absence?->category;
            $boardedAt = $boardedAtMap[$e->student_id] ?? null;

            return [
                'student_id' => $e->student_id,
                'code' => $e->student->code,
                'full_name' => $e->student->full_name,
                'grade' => $e->student->grade,
                'class_name' => $e->student->class_name,
                'parent_phone' => $e->student->parent_phone,
                'pickup_point' => $e->pickup_point,
                'boarded_at' => $boardedAt,
                'status' => $status,
                'display_status' => $this->displayStatus($status, $category),
                'absence_type' => $absence?->absence_type,
                'category' => $category,
                'reason_code' => $absence?->reason_code,
                'absence_reason' => $absence?->absence_reason,
            ];
        })->values()->all();

        $summary = $this->buildSummary($items);
        $session = $this->sessionState($day, $storageShift);

        return [
            'day' => [
                'id' => $day->id,
                'program_id' => $day->program_id,
                'scheduled_date' => $day->scheduled_date->toDateString(),
                'program_name' => $program->name,
                'departure_time' => $program->departure_time,
                'return_time' => $program->return_time,
                'settings' => $program->settings ?? [],
                'driver_name' => $day->effectiveDriver()?->full_name,
            ],
            'shift' => ($multiSlot && $this->shiftResolver->perShiftAttendanceReady()) ? $storageShift : null,
            'multi_slot' => $multiSlot && $this->shiftResolver->perShiftAttendanceReady(),
            'items' => $items,
            'summary' => $summary,
            'effective_count' => $summary['present'],
            'expected_count' => $summary['total'],
            'attendance_status' => $session['status'],
            'attendance_lock_version' => $session['lock_version'],
            'attendance_confirmed_at' => $session['confirmed_at']?->toIso8601String(),
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
        ?string $shift = null,
    ): void {
        DB::transaction(function () use ($day, $studentId, $type, $reason, $actorId, $source, $category, $reasonCode, $syncExecution, $shift) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->loadMissing('program');
            $program = $day->program;
            abort_if($program === null, 404);
            $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);

            $identity = $this->shiftResolver->absenceIdentityAttributes($day->id, $studentId, $storageShift);

            $existed = TpDayAbsence::query()
                ->where($identity)
                ->exists();

            $resolvedCategory = $category ?? $this->categoryFromAbsenceType($type);

            TpDayAbsence::query()->updateOrCreate(
                $identity,
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

            if (! $existed && $storageShift === 'all') {
                $day->decrement('expected_count');
            }

            if ($syncExecution) {
                $this->syncExecutionLogIfInProgress($day, $studentId, $type, $actorId);
            }

            $this->touchDraftStatus($day, $storageShift);

            $this->audit->log($actorId, 'absence.marked', $day, $day->program, metadata: [
                'student_id' => $studentId,
                'type' => $type,
                'source' => $source,
                'shift' => $storageShift,
            ]);
        });
    }

    public function unmarkAbsent(TpProgramDay $day, int $studentId, ?int $actorId, ?string $shift = null): void
    {
        DB::transaction(function () use ($day, $studentId, $actorId, $shift) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->loadMissing('program');
            $program = $day->program;
            abort_if($program === null, 404);
            $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);

            $identity = $this->shiftResolver->absenceIdentityAttributes($day->id, $studentId, $storageShift);

            $deleted = TpDayAbsence::query()
                ->where($identity)
                ->delete();

            if ($deleted && $storageShift === 'all') {
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

            $this->touchDraftStatus($day, $storageShift);
            $this->audit->log($actorId, 'absence.unmarked', $day, $day->program, metadata: [
                'student_id' => $studentId,
                'shift' => $storageShift,
            ]);
        });
    }

    public function markPresent(TpProgramDay $day, int $studentId, ?int $actorId, ?string $shift = null): void
    {
        $this->unmarkAbsent($day, $studentId, $actorId, $shift);
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
        ?string $shift = null,
    ): void {
        // Wrap all students in a single outer transaction so the batch is atomic.
        // Each inner markAbsent() has its own lockForUpdate — they nest within this transaction.
        DB::transaction(function () use ($day, $studentIds, $type, $reason, $actorId, $category, $reasonCode, $shift) {
            foreach ($studentIds as $sid) {
                $this->markAbsent($day, (int) $sid, $type, $reason, $actorId, 'dispatcher', $category, $reasonCode, true, $shift);
            }
        });
    }

    public function markAllPresent(TpProgramDay $day, ?int $actorId, ?string $shift = null): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        abort_if($program === null, 404);
        $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);

        $query = TpDayAbsence::query()->where('program_day_id', $day->id);
        if ($this->shiftResolver->perShiftAttendanceReady()) {
            if ($storageShift === 'all') {
                $query->where('shift', 'all');
            } else {
                $query->where('shift', $storageShift);
            }
        }

        $studentIds = $query->pluck('student_id')->all();

        foreach ($studentIds as $sid) {
            $this->unmarkAbsent($day, (int) $sid, $actorId, $shift);
        }
    }

    public function saveDraft(TpProgramDay $day, ?int $actorId, ?string $shift = null): TpProgramDay
    {
        return DB::transaction(function () use ($day, $actorId, $shift) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->loadMissing('program');
            $program = $day->program;
            abort_if($program === null, 404);
            $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);
            $session = $this->sessionState($day, $storageShift);

            if ($session['status'] === self::STATUS_CONFIRMED) {
                abort(422, 'Điểm danh đã xác nhận, không thể lưu nháp.');
            }

            $this->updateSessionStatus($day, $storageShift, self::STATUS_DRAFT, null, null, $session['lock_version']);
            $this->audit->log($actorId, 'attendance.draft_saved', $day, $day->program, metadata: ['shift' => $storageShift]);

            return $day->fresh();
        });
    }

    public function confirmAttendance(TpProgramDay $day, int $expectedLockVersion, ?int $actorId, ?string $shift = null): TpProgramDay
    {
        return DB::transaction(function () use ($day, $expectedLockVersion, $actorId, $shift) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->loadMissing('program');
            $program = $day->program;
            abort_if($program === null, 404);
            $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);
            $session = $this->sessionState($day, $storageShift);

            if ($session['lock_version'] !== $expectedLockVersion) {
                abort(409, 'Phiên điểm danh đã được cập nhật bởi người khác. Vui lòng tải lại.');
            }

            $payload = $this->getAttendance($day, $shift);
            if ($payload['missing_reason_count'] > 0) {
                abort(422, 'Còn học sinh vắng chưa có lý do vắng.');
            }

            $this->updateSessionStatus(
                $day,
                $storageShift,
                self::STATUS_CONFIRMED,
                now(),
                $actorId,
                $expectedLockVersion,
                $expectedLockVersion + 1,
            );

            $fresh = $day->fresh();
            $this->audit->log($actorId, 'attendance.confirmed', $fresh, $fresh->program, metadata: ['shift' => $storageShift]);

            return $fresh;
        });
    }

    public function reopenAttendance(TpProgramDay $day, ?int $actorId, ?string $shift = null): TpProgramDay
    {
        return DB::transaction(function () use ($day, $actorId, $shift) {
            $day = TpProgramDay::query()->lockForUpdate()->findOrFail($day->id);
            $day->loadMissing('program');
            $program = $day->program;
            abort_if($program === null, 404);
            $storageShift = $this->shiftResolver->resolveStorageShift($program, $shift);

            $this->updateSessionStatus($day, $storageShift, self::STATUS_DRAFT, null, null, null);
            $this->audit->log($actorId, 'attendance.reopened', $day, $day->program, metadata: ['shift' => $storageShift]);

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
            $note = trim((string) ($item['absence_reason'] ?? ''));
            if (empty($item['reason_code']) && $note === '') {
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

    private function touchDraftStatus(TpProgramDay $day, string $storageShift): void
    {
        $session = $this->sessionState($day, $storageShift);
        if ($session['status'] === self::STATUS_CONFIRMED) {
            return;
        }
        if ($session['status'] !== self::STATUS_DRAFT) {
            $this->updateSessionStatus($day, $storageShift, self::STATUS_DRAFT, null, null, $session['lock_version']);
        }
    }

    /**
     * @return array{status: string, lock_version: int, confirmed_at: ?\Carbon\Carbon}
     */
    private function sessionState(TpProgramDay $day, string $storageShift): array
    {
        if ($storageShift !== 'all' && ! $this->shiftResolver->perShiftAttendanceReady()) {
            $storageShift = 'all';
        }

        if ($storageShift === 'all') {
            return [
                'status' => $day->attendance_status ?? self::STATUS_NOT_STARTED,
                'lock_version' => (int) ($day->attendance_lock_version ?? 0),
                'confirmed_at' => $day->attendance_confirmed_at,
            ];
        }

        $statusCol = "{$storageShift}_attendance_status";
        $lockCol = "{$storageShift}_attendance_lock_version";
        $confirmedCol = "{$storageShift}_attendance_confirmed_at";

        return [
            'status' => $day->{$statusCol} ?? self::STATUS_NOT_STARTED,
            'lock_version' => (int) ($day->{$lockCol} ?? 0),
            'confirmed_at' => $day->{$confirmedCol},
        ];
    }

    private function updateSessionStatus(
        TpProgramDay $day,
        string $storageShift,
        string $status,
        ?\Carbon\Carbon $confirmedAt,
        ?int $confirmedBy,
        ?int $expectedLockVersion,
        ?int $newLockVersion = null,
    ): void {
        if ($storageShift !== 'all' && ! $this->shiftResolver->perShiftAttendanceReady()) {
            $storageShift = 'all';
        }

        if ($storageShift === 'all') {
            $query = TpProgramDay::query()->where('id', $day->id);
            if ($expectedLockVersion !== null) {
                $query->where('attendance_lock_version', $expectedLockVersion);
            }
            $updated = $query->update(array_filter([
                'attendance_status' => $status,
                'attendance_confirmed_at' => $confirmedAt,
                'attendance_confirmed_by' => $confirmedBy,
                'attendance_lock_version' => $newLockVersion,
            ], fn ($v) => $v !== null));

            if ($expectedLockVersion !== null && $updated !== 1) {
                abort(409, 'Phiên điểm danh đã được cập nhật bởi người khác. Vui lòng tải lại.');
            }

            return;
        }

        $statusCol = "{$storageShift}_attendance_status";
        $lockCol = "{$storageShift}_attendance_lock_version";
        $confirmedAtCol = "{$storageShift}_attendance_confirmed_at";
        $confirmedByCol = "{$storageShift}_attendance_confirmed_by";

        $payload = [
            $statusCol => $status,
            $confirmedAtCol => $confirmedAt,
            $confirmedByCol => $confirmedBy,
        ];
        if ($newLockVersion !== null) {
            $payload[$lockCol] = $newLockVersion;
        }

        $query = TpProgramDay::query()->where('id', $day->id);
        if ($expectedLockVersion !== null) {
            $query->where($lockCol, $expectedLockVersion);
        }
        $updated = $query->update($payload);

        if ($expectedLockVersion !== null && $updated !== 1) {
            abort(409, 'Phiên điểm danh đã được cập nhật bởi người khác. Vui lòng tải lại.');
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

    /**
     * @return array<int, string|null> student_id => ISO8601 boarded_at
     */
    private function boardedAtMapForDay(TpProgramDay $day): array
    {
        $execution = $day->execution()->first();
        if (! $execution) {
            return [];
        }

        $map = [];
        foreach ($execution->studentLogs()->get(['student_id', 'boarded_at', 'final_status']) as $log) {
            if ($log->final_status === TpTripStudentLog::FINAL_BOARDED && $log->boarded_at) {
                $map[(int) $log->student_id] = $log->boarded_at->toIso8601String();
            }
        }

        return $map;
    }

    private function executionAbsenceType(string $type): string
    {
        if (in_array($type, ['parent_notified', 'no_notice', 'late_cancel'], true)) {
            return $type;
        }

        return 'no_notice';
    }
}
