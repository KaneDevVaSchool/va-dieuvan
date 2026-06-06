<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StandaloneTripCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_create_list_and_show_standalone_cost(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('driver');

        $this->actingAs($driver);

        $create = $this->postJson('/api/trip-costs', [
            'type' => 'other',
            'amount' => 75000,
            'description' => 'Rửa xe định kỳ',
        ], ['Idempotency-Key' => 'standalone-cost-1']);

        $create->assertCreated();
        $create->assertJsonPath('data.trip_id', null);
        $create->assertJsonPath('data.status', 'submitted');

        $costId = (int) $create->json('data.id');
        $this->assertDatabaseHas('trip_costs', [
            'id' => $costId,
            'trip_id' => null,
            'created_by' => $driver->id,
        ]);

        $list = $this->getJson('/api/trip-costs');
        $list->assertOk();
        $ids = collect($list->json('data.items'))->pluck('id')->all();
        $this->assertContains($costId, $ids);

        $onlyStandalone = $this->getJson('/api/trip-costs?standalone=1');
        $onlyStandalone->assertOk();
        $standaloneIds = collect($onlyStandalone->json('data.items'))->pluck('id')->all();
        $this->assertContains($costId, $standaloneIds);
        $this->assertTrue(
            collect($onlyStandalone->json('data.items'))->every(fn ($row) => ($row['trip_id'] ?? null) === null),
        );

        $show = $this->getJson("/api/trip-costs/{$costId}");
        $show->assertOk();
        $show->assertJsonPath('data.id', $costId);
        $show->assertJsonPath('data.trip_id', null);
    }

    public function test_other_driver_cannot_view_standalone_cost(): void
    {
        $this->seed(RbacSeeder::class);

        $owner = User::factory()->create(['is_active' => true]);
        $owner->assignRole('driver');
        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole('driver');

        $cost = TripCost::create([
            'trip_id' => null,
            'created_by' => $owner->id,
            'type' => 'fuel',
            'amount' => 10000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);

        $this->actingAs($other);
        $this->getJson("/api/trip-costs/{$cost->id}")->assertForbidden();
    }

    public function test_driver_can_submit_cost_linked_to_completed_trip(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-06 10:00:00'));

        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole('driver');

        $driverRow = Driver::query()->create([
            'user_id' => $driver->id,
            'full_name' => 'TX Test',
            'phone' => '0900123456',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);

        $req = DispatchRequest::create([
            'requester_id' => $driver->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-06-05 08:00:00'),
            'status' => 'approved',
            'approved_by' => $driver->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $req->id,
            'dispatcher_id' => $driver->id,
            'driver_id' => $driverRow->id,
            'status' => 'completed',
            'depart_at' => $req->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($driver);

        $create = $this->postJson('/api/trip-costs', [
            'trip_id' => $trip->id,
            'type' => 'toll',
            'amount' => 45000,
            'description' => 'Phí cầu bổ sung',
        ], ['Idempotency-Key' => 'standalone-cost-completed-trip']);

        $create->assertCreated();
        $create->assertJsonPath('data.trip_id', $trip->id);

        $this->assertDatabaseHas('trip_costs', [
            'trip_id' => $trip->id,
            'created_by' => $driver->id,
            'status' => 'submitted',
        ]);
    }
}
