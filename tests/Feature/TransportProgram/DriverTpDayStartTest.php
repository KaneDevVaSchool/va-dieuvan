<?php

namespace Tests\Feature\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Services\TransportProgram\DriverAssignmentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTpDayStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_start_multi_slot_day_after_shift_confirm(): void
    {
        $this->seed(RbacSeeder::class);

        $driver = $this->makeDriver('TX Sáng');
        $date = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-START',
            'name' => 'Đưa đón 2 ca',
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

        $this->actingAs($driver->user);

        $this->postJson("/api/driver/tp-days/{$day->id}/confirm", ['shift' => 'morning'])
            ->assertOk();

        $day->refresh();
        $this->assertNotNull($day->morning_confirmed_at);
        $this->assertNull($day->confirmed_at);

        $this->postJson("/api/driver/tp-days/{$day->id}/start", ['shift' => 'morning'])
            ->assertCreated();

        $list = $this->getJson('/api/driver/tp-days?date_from='.$date.'&date_to='.$date)
            ->assertOk()
            ->json('data.items');

        $this->assertCount(1, $list);
        $this->assertSame('morning', $list[0]['shift']);
        $this->assertSame('in_progress', $list[0]['execution_status']);
    }

    public function test_cannot_start_second_shift_while_first_in_progress(): void
    {
        $this->seed(RbacSeeder::class);

        $driver = $this->makeDriver('TX Full');
        $date = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-BLOCK',
            'name' => 'Đưa đón 2 ca',
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
        app(DriverAssignmentService::class)->assignDriver($day->fresh(), (int) $driver->id, null, null, 'afternoon');

        $this->actingAs($driver->user);
        $this->postJson("/api/driver/tp-days/{$day->id}/confirm", ['shift' => 'morning'])->assertOk();
        $this->postJson("/api/driver/tp-days/{$day->id}/confirm", ['shift' => 'afternoon'])->assertOk();
        $this->postJson("/api/driver/tp-days/{$day->id}/start", ['shift' => 'morning'])->assertCreated();

        $this->postJson("/api/driver/tp-days/{$day->id}/start", ['shift' => 'afternoon'])
            ->assertStatus(422);

        $detail = $this->getJson("/api/driver/tp-days/{$day->id}?shift=afternoon")
            ->assertOk()
            ->json('data');
        $this->assertSame('morning', $detail['blocking_in_progress_shift']);
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
