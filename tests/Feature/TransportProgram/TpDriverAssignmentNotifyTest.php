<?php

namespace Tests\Feature\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Notifications\TpDriverAssignmentNotification;
use App\Services\TransportProgram\DriverAssignmentService;
use App\Services\TransportProgram\TpDriverAssignmentNotifyService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TpDriverAssignmentNotifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_driver_change_notifies_previous_and_new_driver(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $userOld = User::factory()->create(['is_active' => true]);
        $userOld->assignRole('driver');
        $driverOld = Driver::query()->create([
            'user_id' => $userOld->id,
            'full_name' => 'TX Cũ',
            'phone' => '0900111001',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $userNew = User::factory()->create(['is_active' => true]);
        $userNew->assignRole('driver');
        $driverNew = Driver::query()->create([
            'user_id' => $userNew->id,
            'full_name' => 'TX Mới',
            'phone' => '0900111002',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $date = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-NOTIFY',
            'name' => 'Đưa đón notify',
            'status' => 'active',
            'start_date' => $date,
            'end_date' => $date,
            'departure_time' => '06:30',
            'default_driver_id' => $driverOld->id,
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        ]);

        $day = TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 5,
        ]);

        app(DriverAssignmentService::class)->assignDriver($day, (int) $driverNew->id, null, null);

        Notification::assertSentTo(
            $userOld,
            TpDriverAssignmentNotification::class,
            fn (TpDriverAssignmentNotification $n) => $n->changeType === 'removed'
                && $n->driverRole === 'main'
                && $n->programDayId === (int) $day->id,
        );

        Notification::assertSentTo(
            $userNew,
            TpDriverAssignmentNotification::class,
            fn (TpDriverAssignmentNotification $n) => $n->changeType === 'assigned'
                && $n->driverRole === 'main'
                && $n->programDayId === (int) $day->id,
        );
    }

    public function test_program_default_driver_change_notifies_both_drivers(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $userOld = User::factory()->create(['is_active' => true]);
        $userOld->assignRole('driver');
        $driverOld = Driver::query()->create([
            'user_id' => $userOld->id,
            'full_name' => 'TX Mặc định cũ',
            'phone' => '0900222001',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $userNew = User::factory()->create(['is_active' => true]);
        $userNew->assignRole('driver');
        $driverNew = Driver::query()->create([
            'user_id' => $userNew->id,
            'full_name' => 'TX Mặc định mới',
            'phone' => '0900222002',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $program = TpProgram::query()->create([
            'code' => 'TP-DEF',
            'name' => 'Chương trình mặc định',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'departure_time' => '07:00',
            'default_driver_id' => $driverOld->id,
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        ]);

        app(TpDriverAssignmentNotifyService::class)->notifyProgramDefaultDriverChange(
            $program,
            $driverOld->id,
            $driverNew->id,
            backup: false,
        );

        Notification::assertSentTo(
            $userOld,
            TpDriverAssignmentNotification::class,
            fn (TpDriverAssignmentNotification $n) => $n->changeType === 'removed' && $n->scope === 'program',
        );

        Notification::assertSentTo(
            $userNew,
            TpDriverAssignmentNotification::class,
            fn (TpDriverAssignmentNotification $n) => $n->changeType === 'assigned' && $n->scope === 'program',
        );
    }
}
