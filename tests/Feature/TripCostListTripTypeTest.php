<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripCostListTripTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_includes_dispatch_request_trip_type_and_filters_by_trip_type(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('dispatcher');

        $businessReq = DispatchRequest::create([
            'requester_id' => $viewer->id,
            'trip_type' => 'business',
            'origin' => 'HQ',
            'destination' => 'Site',
            'depart_at' => Carbon::parse('2026-04-10 10:00:00'),
            'status' => 'approved',
            'approved_by' => $viewer->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
        ]);
        $businessTrip = Trip::create([
            'dispatch_request_id' => $businessReq->id,
            'dispatcher_id' => $viewer->id,
            'status' => 'assigned',
            'depart_at' => $businessReq->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $cargoReq = DispatchRequest::create([
            'requester_id' => $viewer->id,
            'trip_type' => 'cargo',
            'origin' => 'Kho',
            'destination' => 'Trường',
            'depart_at' => Carbon::parse('2026-04-11 10:00:00'),
            'status' => 'approved',
            'approved_by' => $viewer->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
        ]);
        $cargoTrip = Trip::create([
            'dispatch_request_id' => $cargoReq->id,
            'dispatcher_id' => $viewer->id,
            'status' => 'assigned',
            'depart_at' => $cargoReq->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        TripCost::create([
            'trip_id' => $businessTrip->id,
            'created_by' => $viewer->id,
            'type' => 'fuel',
            'amount' => 100000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);
        TripCost::create([
            'trip_id' => $cargoTrip->id,
            'created_by' => $viewer->id,
            'type' => 'toll',
            'amount' => 50000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);

        $this->actingAs($viewer);

        $all = $this->getJson('/api/trip-costs?per_page=20');
        $all->assertOk();
        $items = collect($all->json('data.items'));
        $this->assertCount(2, $items);

        $withType = $items->first(fn (array $row) => ($row['trip']['dispatch_request']['trip_type'] ?? null) === 'business');
        $this->assertNotNull($withType);

        $filtered = $this->getJson('/api/trip-costs?trip_type=business&per_page=20');
        $filtered->assertOk();
        $filteredItems = collect($filtered->json('data.items'));
        $this->assertCount(1, $filteredItems);
        $this->assertSame('business', $filteredItems->first()['trip']['dispatch_request']['trip_type'] ?? null);
    }
}
