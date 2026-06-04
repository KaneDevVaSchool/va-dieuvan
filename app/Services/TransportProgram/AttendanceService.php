<?php

namespace App\Services\TransportProgram;

use App\Models\TpDayAbsence;
use App\Models\TpEnrollment;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * @return array{items: array<int, array>, effective_count: int, expected_count: int}
     */
    public function getAttendance(TpProgramDay $day): array
    {
        $day->loadMissing('program');
        $date = $day->scheduled_date->toDateString();

        $students = TpEnrollment::query()
            ->with('student')
            ->where('program_id', $day->program_id)
            ->whereNull('unenrolled_at')
            ->where('enrolled_at', '<=', $day->scheduled_date)
            ->get()
            ->filter(function (TpEnrollment $e) use ($date) {
                if ($e->unenrolled_at && $e->unenrolled_at->toDateString() <= $date) {
                    return false;
                }

                return true;
            });

        $absenceMap = TpDayAbsence::query()
            ->where('program_day_id', $day->id)
            ->get()
            ->keyBy('student_id');

        $items = $students->map(function (TpEnrollment $e) use ($absenceMap) {
            $absence = $absenceMap->get($e->student_id);

            return [
                'student_id' => $e->student_id,
                'code' => $e->student->code,
                'full_name' => $e->student->full_name,
                'grade' => $e->student->grade,
                'class_name' => $e->student->class_name,
                'status' => $absence ? 'absent' : 'attending',
                'absence_type' => $absence?->absence_type,
                'absence_reason' => $absence?->absence_reason,
            ];
        })->values()->all();

        $absentCount = count(array_filter($items, fn ($i) => $i['status'] === 'absent'));

        return [
            'items' => $items,
            'effective_count' => count($items) - $absentCount,
            'expected_count' => $day->expected_count,
        ];
    }

    public function markAbsent(
        TpProgramDay $day,
        int $studentId,
        string $type,
        ?string $reason,
        ?int $actorId,
        string $source = 'dispatcher',
    ): void {
        $existed = TpDayAbsence::query()
            ->where('program_day_id', $day->id)
            ->where('student_id', $studentId)
            ->exists();

        TpDayAbsence::query()->updateOrCreate(
            ['program_day_id' => $day->id, 'student_id' => $studentId],
            [
                'absence_type' => $type,
                'absence_reason' => $reason,
                'recorded_by' => $actorId,
                'recorded_at' => now(),
                'source' => $source,
            ]
        );

        if (! $existed) {
            $day->decrement('expected_count');
        }

        $this->syncExecutionLogIfInProgress($day, $studentId, $type, $actorId);
        $this->audit->log($actorId, 'absence.marked', $day, $day->program, metadata: [
            'student_id' => $studentId,
            'type' => $type,
        ]);
    }

    public function unmarkAbsent(TpProgramDay $day, int $studentId, ?int $actorId): void
    {
        $deleted = TpDayAbsence::query()
            ->where('program_day_id', $day->id)
            ->where('student_id', $studentId)
            ->delete();

        if ($deleted) {
            $day->increment('expected_count');
        }

        $execution = $day->execution()->where('status', TpTripExecution::STATUS_IN_PROGRESS)->first();
        if ($execution) {
            TpTripStudentLog::query()
                ->where('execution_id', $execution->id)
                ->where('student_id', $studentId)
                ->where('final_status', TpTripStudentLog::FINAL_ABSENT)
                ->update([
                    'final_status' => TpTripStudentLog::FINAL_PENDING,
                    'initial_status' => 'expected',
                    'absent_at' => null,
                    'absent_by' => null,
                ]);
        }

        $this->audit->log($actorId, 'absence.unmarked', $day, $day->program, metadata: ['student_id' => $studentId]);
    }

    /**
     * @param  array<int>  $studentIds
     */
    public function markAbsentBulk(TpProgramDay $day, array $studentIds, string $type, ?string $reason, ?int $actorId): void
    {
        foreach ($studentIds as $sid) {
            $this->markAbsent($day, (int) $sid, $type, $reason, $actorId);
        }
    }

    private function syncExecutionLogIfInProgress(TpProgramDay $day, int $studentId, string $type, ?int $actorId): void
    {
        $execution = $day->execution()->where('status', TpTripExecution::STATUS_IN_PROGRESS)->first();
        if (! $execution) {
            return;
        }

        TpTripStudentLog::query()
            ->where('execution_id', $execution->id)
            ->where('student_id', $studentId)
            ->update([
                'initial_status' => 'pre_absent',
                'final_status' => TpTripStudentLog::FINAL_ABSENT,
                'absence_type' => in_array($type, ['parent_notified', 'no_notice', 'late_cancel'], true) ? $type : 'parent_notified',
                'absent_at' => now(),
                'absent_by' => $actorId,
            ]);

        $execution->increment('total_absent');
    }
}
