<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Models\Route;
use App\Models\SchoolCalendar;
use App\Models\StudentPolicy;
use Illuminate\Support\Carbon;

/**
 * Tự động sinh chuyến policy cho một ngày (§4). Idempotent: dựa trên UNIQUE
 * (trip_date, time_slot, route_id) + INSERT IGNORE nên chạy lại / chạy song song
 * không tạo trùng (CRITICAL FIX L2). Không merge HS vào chuyến manual (E8).
 */
class PolicyTripGeneratorService
{
    public function __construct(
        private readonly PolicyTripService $policyTripService,
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public const RESULT_NOT_SERVICE_DAY = 'not_service_day';
    public const RESULT_MISSING_SEMESTER = 'missing_semester';
    public const RESULT_OK = 'ok';

    /**
     * @return array{result:string, created:int, skipped:int, trips:array<int,array>, message:?string}
     */
    public function generateForDate(string $date): array
    {
        $calendar = SchoolCalendar::query()->whereDate('date', $date)->first();

        if (! $calendar || ! $calendar->isServiceDay()) {
            return $this->summary(self::RESULT_NOT_SERVICE_DAY, 0, 0, [], 'Ngày không phải ngày học/bù hoặc chưa có trong lịch — bỏ qua.');
        }

        // [L1] semester phải có sẵn trên lịch — không suy diễn ngầm.
        if ($calendar->semester === null) {
            return $this->summary(self::RESULT_MISSING_SEMESTER, 0, 0, [], 'Lịch ngày này thiếu học kỳ (semester) — không thể sinh chuyến (L1).');
        }

        $semester = (int) $calendar->semester;
        $created = 0;
        $skipped = 0;
        $trips = [];

        foreach (PolicyTrip::TIME_SLOTS as $timeSlot) {
            $routeIds = StudentPolicy::query()
                ->servingOn($date, $timeSlot, $semester)
                ->distinct()
                ->pluck('route_id');

            foreach ($routeIds as $routeId) {
                [$trip, $wasCreated] = $this->ensureTrip($date, $timeSlot, (int) $routeId);

                $wasCreated ? $created++ : $skipped++;

                // E8: không merge HS vào chuyến tạo thủ công.
                if ($trip->generated_by === 'system') {
                    $this->syncStudentsOntoTrip($trip, $date, $timeSlot, $semester);
                }

                $trips[] = $this->presenter->tripRow($trip->refresh());
            }
        }

        return $this->summary(self::RESULT_OK, $created, $skipped, $trips, null);
    }

    /**
     * Tạo (hoặc lấy lại) chuyến cho route+slot+ngày một cách idempotent.
     *
     * @return array{0: PolicyTrip, 1: bool} chuyến và cờ vừa được tạo
     */
    private function ensureTrip(string $date, string $timeSlot, int $routeId): array
    {
        $route = Route::query()->find($routeId);
        $planned = $this->plannedDeparture($timeSlot);

        $inserted = PolicyTrip::insertOrIgnore([
            'trip_date' => $date,
            'time_slot' => $timeSlot,
            'route_id' => $routeId,
            'status' => PolicyTrip::STATUS_SCHEDULED,
            'planned_departure' => $planned,
            'expected_count' => 0,
            'boarded_count' => 0,
            'absent_count' => 0,
            'route_snapshot' => json_encode($this->routeSnapshot($route), JSON_UNESCAPED_UNICODE),
            'generated_at' => now(),
            'generated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trip = PolicyTrip::query()
            ->where('trip_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('route_id', $routeId)
            ->firstOrFail();

        $wasCreated = $inserted > 0;
        if ($wasCreated) {
            $this->policyTripService->recordAudit($trip, null, 'created', null, [
                'generated_by' => 'system',
                'trip_date' => $date,
                'time_slot' => $timeSlot,
                'route_id' => $routeId,
            ]);
        }

        return [$trip, $wasCreated];
    }

    /** Gắn (idempotent) tất cả HS đang phục vụ vào chuyến + đếm lại (§4.3). */
    private function syncStudentsOntoTrip(PolicyTrip $trip, string $date, string $timeSlot, int $semester): void
    {
        $policies = StudentPolicy::query()
            ->servingOn($date, $timeSlot, (int) $trip->route_id)
            ->where('semester', $semester)
            ->get(['id', 'student_id']);

        if ($policies->isNotEmpty()) {
            $now = now();
            $rows = $policies->map(fn (StudentPolicy $p) => [
                'policy_trip_id' => $trip->id,
                'student_id' => $p->student_id,
                'student_policy_id' => $p->id,
                'expected' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            PolicyTripStudent::insertOrIgnore($rows);
        }

        $this->policyTripService->refreshCounts($trip);
    }

    private function plannedDeparture(string $timeSlot): string
    {
        $time = (string) config("p2p.departure_times.{$timeSlot}", '06:00');

        return Carbon::createFromFormat('H:i', $time)->format('H:i:s');
    }

    private function routeSnapshot(?Route $route): array
    {
        if (! $route) {
            return ['captured_at' => now()->toIso8601String()];
        }

        return [
            'id' => $route->id,
            'name' => $route->name,
            'type' => $route->type,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function summary(string $result, int $created, int $skipped, array $trips, ?string $message): array
    {
        return [
            'result' => $result,
            'created' => $created,
            'skipped' => $skipped,
            'trips' => $trips,
            'message' => $message,
        ];
    }
}
