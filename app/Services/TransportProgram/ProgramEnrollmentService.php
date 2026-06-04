<?php

namespace App\Services\TransportProgram;

use App\Models\TpEnrollment;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramEnrollmentService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * @param  array<int>  $studentIds
     * @return array{enrolled: int, skipped: int}
     */
    public function enrollBulk(TpProgram $program, array $studentIds, ?int $actorId): array
    {
        $existing = TpEnrollment::query()
            ->where('program_id', $program->id)
            ->whereIn('student_id', $studentIds)
            ->whereNull('unenrolled_at')
            ->pluck('student_id')
            ->all();

        $toEnroll = array_values(array_diff($studentIds, $existing));
        $enrolled = 0;

        foreach ($toEnroll as $studentId) {
            TpEnrollment::query()->updateOrCreate(
                ['program_id' => $program->id, 'student_id' => $studentId],
                [
                    'enrolled_at' => now(),
                    'enrolled_by' => $actorId,
                    'unenrolled_at' => null,
                    'unenrolled_by' => null,
                    'unenroll_reason' => null,
                ]
            );
            $enrolled++;
        }

        if ($enrolled > 0) {
            $this->adjustFutureExpectedCounts($program, $enrolled);
            $this->audit->log($actorId, 'enrollment.bulk_enrolled', $program, $program, null, null, [
                'count' => $enrolled,
            ]);
        }

        return ['enrolled' => $enrolled, 'skipped' => count($existing)];
    }

    public function unenroll(TpProgram $program, int $studentId, ?string $reason, ?int $actorId): void
    {
        $enrollment = TpEnrollment::query()
            ->where('program_id', $program->id)
            ->where('student_id', $studentId)
            ->whereNull('unenrolled_at')
            ->firstOrFail();

        $enrollment->update([
            'unenrolled_at' => now(),
            'unenrolled_by' => $actorId,
            'unenroll_reason' => $reason,
        ]);

        $this->adjustFutureExpectedCounts($program, -1);
        $this->audit->log($actorId, 'enrollment.unenrolled', $enrollment, $program);
    }

    private function adjustFutureExpectedCounts(TpProgram $program, int $delta): void
    {
        $today = Carbon::today()->toDateString();

        TpProgramDay::query()
            ->where('program_id', $program->id)
            ->where('day_type', TpProgramDay::DAY_OPERATING)
            ->whereDate('scheduled_date', '>=', $today)
            ->whereDoesntHave('execution')
            ->update([
                'expected_count' => DB::raw('CASE WHEN expected_count + ('.(int) $delta.') < 0 THEN 0 ELSE expected_count + ('.(int) $delta.') END'),
            ]);
    }
}
