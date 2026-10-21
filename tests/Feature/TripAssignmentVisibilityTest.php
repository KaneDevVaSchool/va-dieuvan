<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Gán chuyến ở admin → tài xế thấy chuyến, người đề xuất thấy thông tin điều phối. */
class TripAssignmentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $dispatcher;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RbacSeeder::class);
        $this->dispatcher = User::factory()->create(['is_active' => true]);
        $this->dispatcher->assignRole('dispatcher');
        $this->requester = User::factory()->create(['is_active' => true]);
    }

    private function driver(string $name, string $plate): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');

        return [
            $user,
            Driver::create(['full_name' => $name, 'phone' => '09'.random_int(10000000, 99999999), 'user_id' => $user->id]),
            Vehicle::create(['license_plate' => $plate, 'type' => 'minibus']),
        ];
    }

    private function approvedTrip(array $attributes = []): Trip
    {
        $dr = DispatchRequest::create(array_merge([
            'requester_id' => $this->requester->id, 'trip_type' => 'door_to_door',
            'origin' => 'A', 'destination' => 'B', 'depart_at' => now()->addHours(5),
            'status' => 'pending', 'source_channel' => 'portal', 'is_urgent' => false, 'paper_status' => 'pending',
        ], $attributes));
        $this->actingAs($this->dispatcher)
            ->postJson("/api/dispatch-requests/{$dr->id}/decision", ['decision' => 'approve'])->assertSuccessful();

        return Trip::where('dispatch_request_id', $dr->id)->firstOrFail();
    }

    private function driverTripIds(User $driverUser): array
    {
        Auth::forgetGuards();
        $from = now()->toDateString();
        $to = now()->addDays(2)->toDateString();

        return collect($this->actingAs($driverUser, 'sanctum')
            ->getJson("/api/driver/trips?date_from=$from&date_to=$to")->assertOk()->json('data.items'))
            ->pluck('id')->all();
    }

    public function test_assigned_driver_sees_trip_and_requester_sees_dispatch_info(): void
    {
        [$driverUser, $driver, $vehicle] = $this->driver('Tai Xe A', '51A-001');
        $trip = $this->approvedTrip();

        $this->actingAs($this->dispatcher)->postJson("/api/trips/{$trip->id}/assign", [
            'lock_version' => $trip->lock_version, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id,
        ])->assertOk()->assertJsonPath('data.status', 'assigned');

        $this->assertSame([$trip->id], $this->driverTripIds($driverUser));

        Auth::forgetGuards();
        $this->actingAs($this->requester, 'sanctum')
            ->getJson('/api/portal/dispatch-requests/'.$trip->dispatch_request_id)->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.trip.status', 'assigned')
            ->assertJsonPath('data.trip.driver.full_name', 'Tai Xe A')
            ->assertJsonPath('data.trip.vehicle.license_plate', '51A-001');
        $this->actingAs($this->requester, 'sanctum')
            ->getJson('/api/portal/dispatch-requests')->assertOk()
            ->assertJsonPath('data.items.0.trip.status', 'assigned')
            ->assertJsonPath('data.items.0.trip.dispatcher.name', $this->dispatcher->name);
    }

    public function test_driver_assigned_only_on_second_leg_sees_trip(): void
    {
        [$firstUser, $firstDriver, $firstVehicle] = $this->driver('Tai Xe 1', '51A-101');
        [$secondUser, $secondDriver, $secondVehicle] = $this->driver('Tai Xe 2', '51A-102');
        $depart = now()->addHours(5);
        $trip = $this->approvedTrip([
            'wizard_snapshot' => ['passengerRows' => [
                ['pickup' => 'A', 'dropoff' => 'B', 'depart_at' => $depart->toIso8601String(), 'return_at' => $depart->copy()->addHour()->toIso8601String()],
                ['pickup' => 'B', 'dropoff' => 'C', 'depart_at' => $depart->copy()->addHours(3)->toIso8601String(), 'return_at' => $depart->copy()->addHours(4)->toIso8601String()],
            ]],
        ]);

        $this->actingAs($this->dispatcher)->postJson("/api/trips/{$trip->id}/assign", [
            'lock_version' => $trip->lock_version,
            'schedule_assignments' => [
                ['key' => 'passenger:0', 'driver_id' => $firstDriver->id, 'vehicle_id' => $firstVehicle->id],
                ['key' => 'passenger:1', 'driver_id' => $secondDriver->id, 'vehicle_id' => $secondVehicle->id],
            ],
        ])->assertOk()->assertJsonPath('data.driver_id', $firstDriver->id);

        $this->assertSame([$trip->id], $this->driverTripIds($firstUser));
        $this->assertSame([$trip->id], $this->driverTripIds($secondUser));
    }
}
