<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripsListExcludesCargoTest extends TestCase
{
    use RefreshDatabase;

    public function test_trips_index_can_exclude_cargo_type(): void
    {
        $this->seed(RbacSeeder::class);

        $u = User::factory()->create();
        $u->assignRole('dispatcher');

        $cargoDr = DispatchRequest::create([
            'requester_id' => $u->id,
            'trip_type' => 'cargo',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);
        $cargoTrip = Trip::create([
            'dispatch_request_id' => $cargoDr->id,
            'dispatcher_id' => $u->id,
            'status' => 'approved',
            'depart_at' => $cargoDr->depart_at,
            'lock_version' => 0,
        ]);

        $p2pDr = DispatchRequest::create([
            'requester_id' => $u->id,
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);
        $p2pTrip = Trip::create([
            'dispatch_request_id' => $p2pDr->id,
            'dispatcher_id' => $u->id,
            'status' => 'approved',
            'depart_at' => $p2pDr->depart_at,
            'lock_version' => 0,
        ]);

        $this->actingAs($u);

        $all = $this->getJson('/api/trips?per_page=50');
        $all->assertSuccessful();
        $ids = collect($all->json('data.items'))->pluck('id')->all();
        $this->assertContains($cargoTrip->id, $ids);
        $this->assertContains($p2pTrip->id, $ids);

        $noCargo = $this->getJson('/api/trips?per_page=50&exclude_trip_type=cargo');
        $noCargo->assertSuccessful();
        $ids2 = collect($noCargo->json('data.items'))->pluck('id')->all();
        $this->assertNotContains($cargoTrip->id, $ids2);
        $this->assertContains($p2pTrip->id, $ids2);
    }
}
