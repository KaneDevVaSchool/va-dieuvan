<?php

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurgeTestDataCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{requester: User, driverUser: User, keptDriverUser: User}
     */
    private function seedTestData(): array
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create();
        $requester->assignRole('internal_user');

        $driverUser = User::factory()->create(['employee_code' => 'TEST01']);
        $driverUser->assignRole('driver');
        $keptDriverUser = User::factory()->create(['employee_code' => 'VA010006']);
        $keptDriverUser->assignRole('driver');

        $vehicle = Vehicle::query()->create(['license_plate' => '51A-TEST', 'status' => 'ready']);
        $driver = Driver::query()->create([
            'user_id' => $driverUser->id,
            'full_name' => 'TX test',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
        DriverComplianceDocument::query()->create(['driver_id' => $driver->id, 'doc_type' => 'license']);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
        ]);
        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $requester->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'approved',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
        ]);
        TripEvent::query()->create(['trip_id' => $trip->id, 'type' => 'note']);

        $today = now()->toDateString();
        $program = TpProgram::query()->create([
            'code' => 'TP-PURGE',
            'name' => 'TP test',
            'status' => 'active',
            'start_date' => $today,
            'end_date' => $today,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'default_driver_id' => $driver->id,
            'default_vehicle_id' => $vehicle->id,
            'runs_on' => ['mon'],
        ]);
        TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $today,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'driver_id' => $driver->id,
        ]);

        return compact('requester', 'driverUser', 'keptDriverUser');
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->seedTestData();

        $exit = Artisan::call('data:purge-test', ['--no-interaction' => true]);

        $this->assertSame(0, $exit);
        $this->assertSame(1, Trip::query()->count());
        $this->assertSame(1, Vehicle::query()->count());
        $this->assertSame(1, TpProgram::query()->count());
    }

    public function test_force_purges_everything_and_revokes_driver_role_but_keeps_users(): void
    {
        ['requester' => $requester, 'driverUser' => $driverUser, 'keptDriverUser' => $otherDriver] = $this->seedTestData();
        $otherDriver->assignRole('dispatcher');

        $exit = Artisan::call('data:purge-test', ['--force' => true, '--no-interaction' => true]);

        $this->assertSame(0, $exit, Artisan::output());
        foreach (['trips', 'trip_events', 'dispatch_requests', 'tp_programs', 'tp_program_days',
            'vehicles', 'drivers', 'driver_compliance_documents'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} chưa được xóa");
        }

        $this->assertNotNull(User::query()->find($requester->id));
        $this->assertTrue($requester->fresh()->hasRole('internal_user'));
        $this->assertNotNull($driverUser->fresh());
        $this->assertFalse($driverUser->fresh()->hasRole('driver'));
        $this->assertFalse($otherDriver->fresh()->hasRole('driver'));
        $this->assertTrue($otherDriver->fresh()->hasRole('dispatcher'));

        $this->assertSame(1, AuditLog::query()->count());
        $this->assertSame('system.test_data_purged', AuditLog::query()->value('event'));
    }
}
