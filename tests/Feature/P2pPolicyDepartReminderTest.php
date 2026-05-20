<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\Driver;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\PolicyTripSlot;
use App\Models\Student;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\P2pPolicyTripDepartReminderNotification;
use App\Notifications\TripAssignedNotification;
use App\Services\P2pPolicy\P2pPolicyDepartReminderService;
use App\Services\P2pPolicy\P2pPolicyTripMaterializer;
use App\Support\P2pPolicy;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class P2pPolicyDepartReminderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{activator: User, driverUser: User, term: P2pPolicyTerm, route: PolicyRoute, driver: Driver}
     */
    private function seedSlotFixture(): array
    {
        $this->seed(RbacSeeder::class);

        $activator = User::factory()->create(['is_active' => true]);
        $activator->assignRole('dispatcher');

        $driverUser = User::factory()->create(['is_active' => true, 'email' => 'driver-p2p@test.local']);
        $driverUser->assignRole('driver');

        $origin = Campus::create(['code' => 'A', 'name' => 'Campus A', 'is_active' => true]);
        $dest = Campus::create(['code' => 'B', 'name' => 'Campus B', 'is_active' => true]);

        $academicTerm = AcademicTerm::create([
            'academic_year' => '2025-2026',
            'term_code' => 'HK1',
            'name' => 'HK1',
            'starts_on' => '2025-08-01',
            'ends_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $term = P2pPolicyTerm::create([
            'academic_term_id' => $academicTerm->id,
            'operating_from' => '2025-08-11',
            'operating_to' => '2025-08-11',
            'weekdays_mask' => 31,
            'default_morning_start' => '06:00:00',
            'default_morning_end' => '07:00:00',
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create(['license_plate' => '51A-P2P', 'status' => 'ready']);
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'full_name' => 'TX P2P',
            'employment_status' => 'active',
        ]);

        $route = PolicyRoute::create([
            'p2p_policy_term_id' => $term->id,
            'name' => 'Route P2P',
            'origin_campus_id' => $origin->id,
            'dest_campus_id' => $dest->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'is_active' => true,
        ]);

        $student = Student::create(['student_code' => 'HS-P2P', 'full_name' => 'HS', 'grade' => '6A']);
        PolicyStudent::create([
            'policy_route_id' => $route->id,
            'student_id' => $student->id,
            'student_code' => 'HS-P2P',
            'student_name' => 'HS',
            'class_name' => '6A',
            'direction' => 'two_way',
            'policy_type' => 'internal',
            'effective_from' => '2025-08-01',
            'is_active' => true,
        ]);

        return compact('activator', 'driverUser', 'term', 'route', 'driver');
    }

    public function test_materialize_does_not_send_trip_assigned_notification(): void
    {
        Notification::fake();
        $fx = $this->seedSlotFixture();

        $materializer = app(P2pPolicyTripMaterializer::class);
        $result = $materializer->materializeSlot(
            $fx['term'],
            $fx['route'],
            Carbon::parse('2025-08-11'),
            P2pPolicy::LEG_MORNING,
            $fx['activator']->id,
        );

        $this->assertSame('created', $result);
        Notification::assertNotSentTo($fx['driverUser'], TripAssignedNotification::class);
    }

    public function test_depart_reminder_command_notifies_and_marks_slot(): void
    {
        Notification::fake();
        $fx = $this->seedSlotFixture();

        $materializer = app(P2pPolicyTripMaterializer::class);
        $materializer->materializeSlot(
            $fx['term'],
            $fx['route'],
            Carbon::parse('2025-08-11'),
            P2pPolicy::LEG_MORNING,
            $fx['activator']->id,
        );

        $slot = PolicyTripSlot::query()->first();
        $this->assertNotNull($slot);
        $trip = Trip::findOrFail($slot->trip_id);
        $depart = Carbon::parse('2025-08-11 07:00:00', 'Asia/Ho_Chi_Minh')->utc();
        $trip->update(['depart_at' => $depart, 'arrive_by' => $depart->copy()->addHour()]);
        Carbon::setTestNow(Carbon::parse('2025-08-11 06:35:00', 'Asia/Ho_Chi_Minh'));

        Notification::fake();

        $sent = app(P2pPolicyDepartReminderService::class)->sendDueReminders();
        $this->assertSame(1, $sent);

        $slot->refresh();
        $this->assertNotNull($slot->depart_reminder_sent_at);

        Notification::assertSentTo($fx['driverUser'], P2pPolicyTripDepartReminderNotification::class);
        Notification::assertSentTo($fx['activator'], P2pPolicyTripDepartReminderNotification::class);

        Notification::fake();
        $again = app(P2pPolicyDepartReminderService::class)->sendDueReminders();
        $this->assertSame(0, $again);

        Carbon::setTestNow();
    }

    public function test_depart_reminder_skips_outside_window(): void
    {
        $fx = $this->seedSlotFixture();
        $materializer = app(P2pPolicyTripMaterializer::class);
        $materializer->materializeSlot(
            $fx['term'],
            $fx['route'],
            Carbon::parse('2025-08-11'),
            P2pPolicy::LEG_MORNING,
            $fx['activator']->id,
        );

        $slot = PolicyTripSlot::query()->first();
        $trip = Trip::findOrFail($slot->trip_id);
        $departFar = Carbon::now('Asia/Ho_Chi_Minh')->addDays(2)->utc();
        $trip->update(['depart_at' => $departFar]);

        Notification::fake();
        $sent = app(P2pPolicyDepartReminderService::class)->sendDueReminders(Carbon::now('Asia/Ho_Chi_Minh'));
        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
    }
}
