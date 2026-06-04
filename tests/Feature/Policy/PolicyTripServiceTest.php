<?php

namespace Tests\Feature\Policy;

use App\Models\Driver;
use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Models\Route;
use App\Models\SchoolCalendar;
use App\Models\Student;
use App\Models\StudentPolicy;
use App\Services\P2pPolicy\PolicyTripGeneratorService;
use App\Services\P2pPolicy\PolicyTripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AC3 (E2 — xung đột tài xế) và AC7 (L3 — đồng bộ khi policy ngừng phục vụ).
 */
class PolicyTripServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspend_sync_marks_future_trip_students_not_expected(): void
    {
        $date = now()->addDays(3)->toDateString();
        $route = Route::create(['name' => 'R1', 'is_active' => true]);
        $student = Student::create(['student_code' => 'HS1', 'full_name' => 'A', 'is_active' => true]);

        SchoolCalendar::create(['school_year' => '2025-2026', 'semester' => 1, 'date' => $date, 'day_type' => 'school_day']);

        $policy = StudentPolicy::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'school_year' => '2025-2026',
            'semester' => 1,
            'time_slot' => 'morning',
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        app(PolicyTripGeneratorService::class)->generateForDate($date);
        $this->assertSame(1, (int) PolicyTrip::first()->expected_count);

        $affected = app(PolicyTripService::class)->syncStudentsOnPolicyStopService($policy->fresh(), null);

        $this->assertSame(1, $affected);
        $entry = PolicyTripStudent::where('student_policy_id', $policy->id)->first();
        $this->assertFalse((bool) $entry->expected);
        $this->assertSame('absent_reported', $entry->absence_reason);
        $this->assertSame(0, (int) PolicyTrip::first()->fresh()->expected_count);
    }

    public function test_driver_conflict_detected_for_same_slot_and_date(): void
    {
        $date = now()->addDays(2)->toDateString();
        $route1 = Route::create(['name' => 'R1', 'is_active' => true]);
        $route2 = Route::create(['name' => 'R2', 'is_active' => true]);
        $driver = Driver::create(['full_name' => 'Tài xế X']);

        PolicyTrip::create([
            'trip_date' => $date,
            'time_slot' => 'morning',
            'route_id' => $route1->id,
            'driver_id' => $driver->id,
            'status' => 'assigned',
        ]);
        $other = PolicyTrip::create([
            'trip_date' => $date,
            'time_slot' => 'morning',
            'route_id' => $route2->id,
            'status' => 'scheduled',
        ]);

        $service = app(PolicyTripService::class);

        $conflict = $service->conflictingTrip($driver->id, $date, 'morning', $other->id);
        $this->assertNotNull($conflict);
        $this->assertSame($route1->id, $conflict->route_id);

        // Khác ca → không xung đột.
        $this->assertFalse($service->driverHasConflict($driver->id, $date, 'afternoon', $other->id));
    }
}
