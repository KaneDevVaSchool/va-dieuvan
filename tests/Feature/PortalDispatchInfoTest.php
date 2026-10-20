<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDispatchInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_receives_assigned_driver_and_vehicle_on_request_detail(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $driver = Driver::create(['full_name' => 'Assigned Driver', 'phone' => '0900000052']);
        $vehicle = Vehicle::create(['license_plate' => 'TEST-52', 'type' => 'minibus']);
        $request = DispatchRequest::create([
            'requester_id' => $requester->id, 'trip_type' => 'cargo',
            'origin' => 'A', 'destination' => 'B', 'depart_at' => now()->addDay(),
            'status' => 'approved', 'source_channel' => 'portal',
        ]);
        Trip::create([
            'dispatch_request_id' => $request->id, 'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id, 'status' => 'assigned', 'depart_at' => $request->depart_at,
        ]);

        $this->actingAs($requester, 'sanctum')->getJson('/api/portal/dispatch-requests/'.$request->id)
            ->assertOk()
            ->assertJsonPath('data.trip.driver.full_name', 'Assigned Driver')
            ->assertJsonPath('data.trip.driver.phone', '0900000052')
            ->assertJsonPath('data.trip.vehicle.license_plate', 'TEST-52')
            ->assertJsonPath('data.trip.vehicle.type', 'minibus');

        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other, 'sanctum')->getJson('/api/portal/dispatch-requests/'.$request->id)->assertForbidden();
    }
}
