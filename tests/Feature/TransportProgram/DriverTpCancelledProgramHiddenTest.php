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

class DriverTpCancelledProgramHiddenTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_program_day_is_hidden_from_driver_list_and_confirm_blocked(): void
    {
        $this->seed(RbacSeeder::class);

        $driver = $this->makeDriver('TX Hủy');
        $date = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-CANCEL',
            'name' => 'Đưa đón sẽ hủy',
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

        // Trước khi hủy: ngày xuất hiện trong danh sách tài xế.
        $before = $this->getJson('/api/driver/tp-days?date_from='.$date.'&date_to='.$date)
            ->assertOk()
            ->json('data.items');
        $this->assertCount(1, $before);

        // Hủy chương trình ở mức TpProgram (ngày con vẫn là "operating").
        $program->update(['status' => TpProgram::STATUS_CANCELLED]);

        // Sau khi hủy: ngày biến mất khỏi danh sách tài xế.
        $after = $this->getJson('/api/driver/tp-days?date_from='.$date.'&date_to='.$date)
            ->assertOk()
            ->json('data.items');
        $this->assertCount(0, $after);

        // Và không thể xác nhận chuyến của chương trình đã hủy.
        $this->postJson("/api/driver/tp-days/{$day->id}/confirm", ['shift' => 'morning'])
            ->assertStatus(422);
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
