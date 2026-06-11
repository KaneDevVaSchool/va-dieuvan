<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripEvent;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * P0 — chặn BOLA/IDOR ở POST /api/trips/{trip}/events:
 * tài xế chỉ được ghi sự kiện (điểm danh) lên chuyến của chính mình.
 */
class DriverTripEventAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeTripForDriver(User $ownerUser): Trip
    {
        $driverRow = Driver::query()->create([
            'user_id' => $ownerUser->id,
            'full_name' => 'TX Owner',
            'phone' => '0900111222',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $req = DispatchRequest::create([
            'requester_id' => $ownerUser->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-06-06 08:00:00'),
            'status' => 'approved',
            'approved_by' => $ownerUser->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
        ]);

        return Trip::create([
            'dispatch_request_id' => $req->id,
            'dispatcher_id' => $ownerUser->id,
            'driver_id' => $driverRow->id,
            'status' => 'in_progress',
            'depart_at' => $req->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_assigned_driver_can_add_pickup_event(): void
    {
        $this->seed(RbacSeeder::class);

        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('driver');
        $trip = $this->makeTripForDriver($owner);

        $this->actingAs($owner);
        $res = $this->postJson("/api/trips/{$trip->id}/events", [
            'type' => 'passenger_pickup',
            'data' => ['row_index' => 0, 'state' => 'picked_up'],
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('trip_events', [
            'trip_id' => $trip->id,
            'type' => 'passenger_pickup',
            'created_by' => $owner->id,
        ]);
    }

    public function test_other_driver_cannot_add_event_to_foreign_trip(): void
    {
        $this->seed(RbacSeeder::class);

        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('driver');
        $trip = $this->makeTripForDriver($owner);

        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole('driver');

        $this->actingAs($other);
        $res = $this->postJson("/api/trips/{$trip->id}/events", [
            'type' => 'passenger_pickup',
            'data' => ['row_index' => 0, 'state' => 'picked_up'],
        ]);

        $res->assertForbidden();
        $this->assertSame(0, TripEvent::query()->where('trip_id', $trip->id)->count());
    }

    public function test_invalid_event_type_is_rejected(): void
    {
        $this->seed(RbacSeeder::class);

        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('driver');
        $trip = $this->makeTripForDriver($owner);

        $this->actingAs($owner);
        $this->postJson("/api/trips/{$trip->id}/events", [
            'type' => 'status_change',
            'data' => ['to' => 'completed'],
        ])->assertStatus(422);
    }
}
