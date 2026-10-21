<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DriverEmailUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function actingDispatcher(): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        return $user;
    }

    public function test_external_driver_can_store_and_update_email(): void
    {
        $dispatcher = $this->actingDispatcher();

        $create = $this->actingAs($dispatcher)
            ->postJson('/api/drivers', [
                'full_name' => 'External Driver',
                'email' => 'ext.driver@example.test',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'ext.driver@example.test');

        $driverId = (int) $create->json('data.id');

        $this->assertDatabaseHas('drivers', [
            'id' => $driverId,
            'email' => 'ext.driver@example.test',
        ]);

        $this->actingAs($dispatcher)
            ->patchJson('/api/drivers/'.$driverId, [
                'email' => 'ext.updated@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'ext.updated@example.test');

        $this->assertDatabaseHas('drivers', [
            'id' => $driverId,
            'email' => 'ext.updated@example.test',
        ]);
    }

    public function test_linked_driver_email_update_syncs_to_user(): void
    {
        $dispatcher = $this->actingDispatcher();

        $user = User::factory()->create(['email' => 'driver.old@example.test']);
        $user->assignRole('driver');

        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Linked Driver',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $this->actingAs($dispatcher)
            ->patchJson('/api/drivers/'.$driver->id, [
                'email' => 'driver.new@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'driver.new@example.test');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'driver.new@example.test',
        ]);
    }

    public function test_unlinked_driver_email_matching_user_links_account_and_shows_trips(): void
    {
        $dispatcher = $this->actingDispatcher();
        $user = User::factory()->create(['email' => 'vietlq@example.test', 'is_active' => true]);
        $driver = Driver::query()->create(['full_name' => 'Lưu Quốc Việt', 'employment_status' => 'active', 'availability_status' => 'available']);
        $request = DispatchRequest::create([
            'requester_id' => $dispatcher->id, 'trip_type' => 'cargo', 'origin' => 'A', 'destination' => 'B',
            'depart_at' => now(), 'status' => 'approved', 'source_channel' => 'portal',
        ]);
        $trip = Trip::create(['dispatch_request_id' => $request->id, 'driver_id' => $driver->id, 'depart_at' => now(), 'status' => 'assigned']);

        $this->actingAs($dispatcher)
            ->patchJson('/api/drivers/'.$driver->id, ['email' => 'VietLQ@example.test'])
            ->assertOk();

        $this->assertSame($user->id, $driver->fresh()->user_id);
        $this->assertTrue($user->fresh()->hasRole('driver'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'driver.user_linked', 'auditable_id' => $driver->id]);

        Auth::forgetGuards();
        $date = now()->toDateString();
        $this->actingAs($user->fresh(), 'sanctum')
            ->getJson("/api/driver/trips?date_from=$date&date_to=$date")
            ->assertOk()->assertJsonPath('data.items.0.id', $trip->id);
    }

    public function test_email_of_user_linked_to_other_driver_is_rejected(): void
    {
        $dispatcher = $this->actingDispatcher();
        $user = User::factory()->create(['email' => 'taken@example.test']);
        Driver::query()->create(['user_id' => $user->id, 'full_name' => 'Existing', 'employment_status' => 'active', 'availability_status' => 'available']);
        $driver = Driver::query()->create(['full_name' => 'Duplicate', 'employment_status' => 'active', 'availability_status' => 'available']);

        $this->actingAs($dispatcher)
            ->patchJson('/api/drivers/'.$driver->id, ['email' => 'taken@example.test'])
            ->assertUnprocessable();

        $this->assertNull($driver->fresh()->user_id);
        $this->assertNull($driver->fresh()->email);
    }
}
