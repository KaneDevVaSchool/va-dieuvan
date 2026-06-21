<?php

namespace Tests\Feature\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Notifications\TpDriverReportedBusyNotification;
use App\Services\TransportProgram\DriverAssignmentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DriverTpReportBusyTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_report_busy_unassigns_and_notifies_dispatch(): void
    {
        Notification::fake();
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $driver = $this->makeDriver('TX Bận');
        $date = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-BUSY',
            'name' => 'Đưa đón báo bận',
            'status' => 'active',
            'start_date' => $date,
            'end_date' => $date,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00', 'arrival' => '07:00'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ]);

        $day = TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 5,
        ]);

        app(DriverAssignmentService::class)->assignDriver($day, (int) $driver->id, null, null, 'morning');
        $this->assertSame((int) $driver->id, (int) $day->fresh()->morning_driver_id);

        $this->actingAs($driver->user);

        $this->postJson("/api/driver/tp-days/{$day->id}/report-busy", [
            'shift' => 'morning',
            'reason' => 'Bận việc gia đình đột xuất',
        ])->assertOk();

        // Tài xế bị gỡ khỏi ca sáng.
        $this->assertNull($day->fresh()->morning_driver_id);

        // Điều phối nhận thông báo báo bận.
        Notification::assertSentTo($dispatcher, TpDriverReportedBusyNotification::class);

        // Ca không còn xuất hiện trong danh sách của tài xế.
        $items = $this->getJson('/api/driver/tp-days?date_from='.$date.'&date_to='.$date)
            ->assertOk()
            ->json('data.items');
        $morning = collect($items)->firstWhere('shift', 'morning');
        $this->assertNull($morning);
    }

    private function makeDriver(string $name): Driver
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');

        return Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => $name,
            'phone' => '0900'.random_int(100000, 999999),
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
    }
}
