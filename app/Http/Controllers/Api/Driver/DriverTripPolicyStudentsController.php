<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\RouteRun;
use App\Models\Trip;
use App\Support\TripVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTripPolicyStudentsController extends Controller
{
    use ApiResponses;

    public function index(Request $request, Trip $trip): JsonResponse
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $trip->loadMissing('passenger_check_ins');

        /** @var RouteRun|null $run */
        $run = RouteRun::query()
            ->where('trip_id', $trip->id)
            ->with(['routeVersion.route'])
            ->first();

        if (! $run?->routeVersion?->route) {
            return $this->ok(['students' => []]);
        }

        $route = $run->routeVersion->route;
        $runDateStr = $run->run_date instanceof \DateTimeInterface
            ? $run->run_date->format('Y-m-d')
            : (string) $run->run_date;

        $students = $route->students()
            ->where('students.is_active', true)
            ->where(function ($w) use ($runDateStr) {
                $w->whereNull('route_students.starts_on')
                    ->orWhere('route_students.starts_on', '<=', $runDateStr);
            })
            ->where(function ($w) use ($runDateStr) {
                $w->whereNull('route_students.ends_on')
                    ->orWhere('route_students.ends_on', '>=', $runDateStr);
            })
            ->orderBy('students.full_name')
            ->orderBy('students.id')
            ->get();

        $checkIns = is_array($trip->passenger_check_ins) ? $trip->passenger_check_ins : [];

        $payload = $students->map(function ($student) use ($checkIns) {
            $key = 'stu_'.$student->id;
            /** @var array<string,mixed>|null $entry */
            $entry = $checkIns[$key] ?? null;
            $checkedInAt = is_array($entry) && isset($entry['checked_in_at'])
                ? (string) $entry['checked_in_at']
                : null;

            return [
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'grade' => $student->grade,
                'check_in_key' => $key,
                'checked_in_at' => $checkedInAt,
            ];
        });

        return $this->ok(['students' => $payload->values()->all()]);
    }
}
