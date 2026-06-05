<?php

namespace Tests\Feature\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Notifications\TpDriverMorningReminderNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TpDriverMorningReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_morning_reminder_command_notifies_assigned_driver(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'TX Đưa đón',
            'phone' => '0900111222',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $today = now('Asia/Ho_Chi_Minh')->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-TEST',
            'name' => 'Đưa đón thử',
            'status' => 'active',
            'start_date' => $today,
            'end_date' => $today,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'default_driver_id' => $driver->id,
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00', 'arrival' => '07:15'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ]);

        TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $today,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 12,
        ]);

        $exit = Artisan::call('tp:remind-driver-morning-shifts', ['--date' => $today]);
        $this->assertSame(0, $exit);

        Notification::assertSentTo(
            $user,
            TpDriverMorningReminderNotification::class,
            fn (TpDriverMorningReminderNotification $n) => $n->departureTime === '06:00'
                && $n->scheduledDate === $today,
        );
    }
}
