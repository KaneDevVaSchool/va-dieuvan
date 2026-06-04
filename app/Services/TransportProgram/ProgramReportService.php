<?php

namespace App\Services\TransportProgram;

use App\Models\TpDayAbsence;
use App\Models\TpEnrollment;
use App\Models\TpProgram;
use Carbon\Carbon;

class ProgramReportService
{
    /**
     * @return array{columns: array<int, string>, rows: array<int, array>}
     */
    public function absencePivot(TpProgram $program, string $from, string $to): array
    {
        $days = $program->days()
            ->whereBetween('scheduled_date', [$from, $to])
            ->where('day_type', 'operating')
            ->orderBy('scheduled_date')
            ->pluck('scheduled_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $enrollments = TpEnrollment::query()
            ->with('student')
            ->where('program_id', $program->id)
            ->whereNull('unenrolled_at')
            ->get();

        $absences = TpDayAbsence::query()
            ->whereIn('program_day_id', $program->days()->whereBetween('scheduled_date', [$from, $to])->pluck('id'))
            ->get()
            ->groupBy(fn ($a) => $a->student_id);

        $dayIndex = $program->days()
            ->whereBetween('scheduled_date', [$from, $to])
            ->get()
            ->keyBy(fn ($d) => $d->scheduled_date->toDateString());

        $rows = [];
        foreach ($enrollments as $enrollment) {
            $studentAbsences = $absences->get($enrollment->student_id, collect());
            $cells = [];
            foreach ($days as $date) {
                $day = $dayIndex->get($date);
                $cell = '';
                if ($day) {
                    $abs = $studentAbsences->firstWhere('program_day_id', $day->id);
                    $cell = $abs ? $abs->absence_type : '';
                }
                $cells[$date] = $cell;
            }
            $rows[] = [
                'student_id' => $enrollment->student_id,
                'code' => $enrollment->student->code,
                'full_name' => $enrollment->student->full_name,
                'cells' => $cells,
            ];
        }

        return ['columns' => $days, 'rows' => $rows];
    }

    /**
     * @return array{items: array<int, array>, total_estimated: float, total_actual: float}
     */
    public function costReport(TpProgram $program, string $month): array
    {
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $executions = $program->executions()
            ->with(['programDay', 'driver'])
            ->whereHas('programDay', fn ($q) => $q->whereBetween('scheduled_date', [$start, $end]))
            ->get();

        $items = $executions->map(fn ($e) => [
            'execution_id' => $e->id,
            'date' => $e->programDay->scheduled_date->toDateString(),
            'driver_name' => $e->driver_snapshot['full_name'] ?? $e->driver?->full_name,
            'estimated_cost' => $e->estimated_cost,
            'actual_cost' => $e->actual_cost,
            'status' => $e->status,
        ])->all();

        return [
            'items' => $items,
            'total_estimated' => (float) $executions->sum('estimated_cost'),
            'total_actual' => (float) $executions->sum('actual_cost'),
        ];
    }
}
