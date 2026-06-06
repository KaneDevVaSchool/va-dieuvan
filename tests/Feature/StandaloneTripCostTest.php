<?php

namespace Tests\Feature;

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
}
