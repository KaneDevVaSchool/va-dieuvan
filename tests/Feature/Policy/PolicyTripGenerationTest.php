<?php

namespace Tests\Feature\Policy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Models\Route;
use App\Models\SchoolCalendar;
use App\Models\Student;
use App\Models\StudentPolicy;
use App\Services\P2pPolicy\PolicyTripGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AC1 — Tự động sinh chuyến (docs/p2p.md §4, §9).
 */
class PolicyTripGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function makePolicy(string $date = '2026-01-12', string $slot = 'morning'): array
    {
        $route = Route::create(['name' => 'Thông Tây Hội → Bình Thới', 'is_active' => true]);
        $student = Student::create(['student_code' => 'HS001', 'full_name' => 'Nguyễn Văn A', 'is_active' => true]);

        SchoolCalendar::create([
            'school_year' => '2025-2026',
            'semester' => 1,
            'date' => $date,
            'day_type' => 'school_day',
        ]);

        StudentPolicy::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'school_year' => '2025-2026',
            'semester' => 1,
            'time_slot' => $slot,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-06-30',
            'status' => 'active',
        ]);

        return [$route, $student];
    }

    private function generator(): PolicyTripGeneratorService
    {
        return app(PolicyTripGeneratorService::class);
    }

    public function test_generates_trip_with_expected_students_on_school_day(): void
    {
        $this->makePolicy('2026-01-12');

        $result = $this->generator()->generateForDate('2026-01-12');

        $this->assertSame(PolicyTripGeneratorService::RESULT_OK, $result['result']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, PolicyTrip::count());
        $this->assertSame(1, PolicyTripStudent::count());
        $this->assertSame(1, (int) PolicyTrip::first()->expected_count);
    }

    public function test_generation_is_idempotent_on_rerun(): void
    {
        $this->makePolicy('2026-01-12');

        $this->generator()->generateForDate('2026-01-12');
        $second = $this->generator()->generateForDate('2026-01-12');

        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, PolicyTrip::count());
        $this->assertSame(1, PolicyTripStudent::count());
    }

    public function test_skips_non_service_day(): void
    {
        $this->makePolicy('2026-01-12');
        // Đổi ngày thành nghỉ lễ.
        SchoolCalendar::query()->whereDate('date', '2026-01-12')->update(['day_type' => 'holiday']);

        $result = $this->generator()->generateForDate('2026-01-12');

        $this->assertSame(PolicyTripGeneratorService::RESULT_NOT_SERVICE_DAY, $result['result']);
        $this->assertSame(0, PolicyTrip::count());
    }

    public function test_skips_when_semester_missing(): void
    {
        $this->makePolicy('2026-01-12');
        SchoolCalendar::query()->whereDate('date', '2026-01-12')->update(['semester' => null]);

        $result = $this->generator()->generateForDate('2026-01-12');

        $this->assertSame(PolicyTripGeneratorService::RESULT_MISSING_SEMESTER, $result['result']);
        $this->assertSame(0, PolicyTrip::count());
    }
}
