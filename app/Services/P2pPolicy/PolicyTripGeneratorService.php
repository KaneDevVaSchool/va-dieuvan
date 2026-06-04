<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Models\Route;
use App\Models\SchoolCalendar;
use App\Models\StudentPolicy;
use Illuminate\Support\Facades\DB;

class PolicyTripGeneratorService
{
    public function __construct(
        private readonly PolicyTripService $policyTripService,
    ) {}

    /**
     * @return array{created: int, skipped: int, trips: array<int, array>}
     */
    public function generateForDate(string $date): array
    {
        $calendar = SchoolCalendar::query()->whereDate('date', $date)->first();

        if (! $calendar || ! in_array($calendar->day_type, ['school_day', 'makeup_day'], true)) {
            return ['created' => 0, 'skipped' => 0, 'trips' => [], 'message' => 'Ngày không phải ngày học hoặc chưa có trong lịch.'];
        }

        if ($calendar->semester === null) {
            return ['created' => 0, 'skipped' => 0, 'trips' => [], 'message' => 'Thiếu học kỳ (semester) trên lịch ngày này (L1).'];
        }

        $semester = (int) $calendar->semester;
        $created = 0;
        $skipped = 0;
        $tripPayloads = [];

        foreach (['morning', 'afternoon'] as $timeSlot) {
            $routeIds = StudentPolicy::query()
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->where('time_slot', $timeSlot)
                ->where('semester', $semester)
                ->whereDate('effective_from', '<=', $date)
                ->whereDate('effective_to', '>=', $date)
                ->distinct()
                ->pluck('route_id');

            foreach ($routeIds as $routeId) {
                $result = $this->ensureTripForRouteSlot($date, $timeSlot, (int) $routeId, $semester);
                if ($result === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
                if ($result !== 'error') {
                    $trip = PolicyTrip::query()
                        ->whereDate('trip_date', $date)
                        ->where('time_slot', $timeSlot)
                        ->where('route_id', $routeId)
                        ->first();
                    if ($trip) {
                        $tripPayloads[] = $this->policyTripService->tripToListArray($trip);
                    }
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'trips' => $tripPayloads,
            'message' => null,
        ];
    }

    private function ensureTripForRouteSlot(string $date, string $timeSlot, int $routeId, int $semester): string
    {
        $existing = PolicyTrip::query()
            ->whereDate('trip_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('route_id', $routeId)
            ->first();

        if ($existing) {
            $this->syncStudentsOntoTrip($existing, $date, $timeSlot, $routeId, $semester);

            return 'skipped';
        }

        $route = Route::query()->find($routeId);
        $planned = $timeSlot === 'morning' ? '06:00:00' : '15:30:00';

        return DB::transaction(function () use ($date, $timeSlot, $routeId, $semester, $route, $planned) {
            $trip = PolicyTrip::create([
                'trip_date' => $date,
                'time_slot' => $timeSlot,
                'route_id' => $routeId,
                'status' => 'scheduled',
                'planned_departure' => $planned,
                'generated_at' => now(),
                'generated_by' => 'system',
                'route_snapshot' => $route ? ['id' => $route->id, 'name' => $route->name] : null,
            ]);

            $this->syncStudentsOntoTrip($trip, $date, $timeSlot, $routeId, $semester);
            $this->policyTripService->refreshTripCounts($trip);

            return 'created';
        });
    }

    private function syncStudentsOntoTrip(
        PolicyTrip $trip,
        string $date,
        string $timeSlot,
        int $routeId,
        int $semester,
    ): void {
        $policies = StudentPolicy::query()
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->where('route_id', $routeId)
            ->where('time_slot', $timeSlot)
            ->where('semester', $semester)
            ->whereDate('effective_from', '<=', $date)
            ->whereDate('effective_to', '>=', $date)
            ->get();

        foreach ($policies as $policy) {
            PolicyTripStudent::firstOrCreate(
                [
                    'policy_trip_id' => $trip->id,
                    'student_id' => $policy->student_id,
                ],
                [
                    'student_policy_id' => $policy->id,
                    'expected' => true,
                ],
            );
        }

        $this->policyTripService->refreshTripCounts($trip);
    }
}
