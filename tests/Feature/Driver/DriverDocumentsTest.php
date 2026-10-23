<?php

namespace Tests\Feature\Driver;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DriverDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-10-23 03:00:00', 'UTC'));
    }

    public function test_driver_sees_assigned_vehicle_documents_with_expiry_alerts(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Nguyễn Văn Tài',
            'employment_status' => 'active',
            'availability_status' => 'available',
            'license_expires_at' => '2027-06-01',
        ]);
        Vehicle::query()->create([
            'license_plate' => '51B-12345',
            'type' => 'Xe 29 chỗ',
            'status' => 'ready',
            'default_driver_id' => $driver->id,
            'inspection_expires_at' => '2026-10-20',
            'insurance_expires_at' => '2026-11-02',
            'road_fee_expires_at' => '2027-03-01',
            'last_maintenance_at' => '2026-03-01',
        ]);
        Vehicle::query()->create(['license_plate' => '51B-99999', 'status' => 'ready']);

        $res = $this->actingAs($user)->getJson('/api/driver/documents')->assertOk();

        $res->assertJsonPath('data.soon_days', 30)
            ->assertJsonPath('data.license.state', 'ok')
            ->assertJsonCount(1, 'data.vehicles')
            ->assertJsonPath('data.vehicles.0.license_plate', '51B-12345')
            ->assertJsonPath('data.vehicles.0.documents.0.state', 'exp')
            ->assertJsonPath('data.vehicles.0.documents.1.state', 'soon')
            ->assertJsonPath('data.vehicles.0.documents.2.state', 'ok')
            ->assertJsonPath('data.vehicles.0.maintenance.state', 'stale');

        $this->assertSame(['inspection', 'insurance', 'maintenance'], array_column($res->json('data.alerts'), 'key'));
    }

    public function test_dispatcher_cannot_use_driver_endpoint(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');

        $this->actingAs($user)->getJson('/api/driver/documents')->assertForbidden();
    }
}
