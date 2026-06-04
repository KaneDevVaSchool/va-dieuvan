<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\PolicyTripAbsenceReportRequest;
use App\Models\PolicyTripStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class PolicyTripAbsenceReportController extends Controller
{
    use ApiResponses;

    public function __invoke(PolicyTripAbsenceReportRequest $request): JsonResponse
    {
        $data = $request->validated();
        $weekStart = Carbon::parse($data['week_start'])->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(4);

        $q = PolicyTripStudent::query()
            ->whereNotNull('absence_reason')
            ->whereHas('policyTrip', function ($tripQ) use ($weekStart, $weekEnd, $data) {
                $tripQ->whereBetween('trip_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]);
                if (! empty($data['time_slot'])) {
                    $tripQ->where('time_slot', $data['time_slot']);
                }
                if (! empty($data['route_id'])) {
                    $tripQ->where('route_id', $data['route_id']);
                }
            })
            ->with(['student', 'policyTrip']);

        if (! empty($data['class'])) {
            $class = $data['class'];
            $q->whereHas('student', fn ($s) => $s->where('grade', 'like', '%'.$class.'%'));
        }

        $entries = $q->get();

        $byStudent = [];
        foreach ($entries as $entry) {
            $sid = $entry->student_id;
            if (! isset($byStudent[$sid])) {
                $byStudent[$sid] = [
                    'student_id' => $sid,
                    'student_name' => $entry->student?->full_name ?? (string) $sid,
                    'class_name' => $entry->student?->grade,
                    'days' => [],
                    'total_absent' => 0,
                    'absent_reported' => 0,
                    'absent_no_notice' => 0,
                ];
            }

            $date = $entry->policyTrip->trip_date->format('Y-m-d');
            $byStudent[$sid]['days'][$date] = [
                'absence_reason' => $entry->absence_reason,
            ];
            $byStudent[$sid]['total_absent']++;
            if ($entry->absence_reason === 'absent_reported' || $entry->absence_reason === 'late_cancellation') {
                $byStudent[$sid]['absent_reported']++;
            }
            if ($entry->absence_reason === 'absent_no_notice') {
                $byStudent[$sid]['absent_no_notice']++;
            }
        }

        return $this->ok([
            'week_start' => $weekStart->toDateString(),
            'students' => array_values($byStudent),
        ]);
    }
}
