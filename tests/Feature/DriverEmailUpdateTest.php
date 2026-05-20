<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
