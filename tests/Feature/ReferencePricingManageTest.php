<?php

namespace Tests\Feature;

use App\Models\CargoFareRate;
use App\Models\PassengerFareRate;
use App\Models\ReferencePricingRevision;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Database\Seeders\ReferencePricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferencePricingManageTest extends TestCase
{
    use RefreshDatabase;

    private function actingDispatcher(): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        return $user;
    }

    public function test_revisions_requires_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ReferencePricingSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('internal_user');

        $row = PassengerFareRate::query()->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/reference-pricing/revisions?type=passenger_fare_rate&id='.$row->id)
            ->assertForbidden();
    }

    public function test_patch_passenger_fare_creates_revision_and_updates(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ReferencePricingSeeder::class);
        $user = $this->actingDispatcher();

        $row = PassengerFareRate::query()->firstOrFail();
        $oldSeat7 = $row->seat_7;

        $this->actingAs($user)
            ->patchJson('/api/reference-pricing/passenger-fares/'.$row->id, [
                'seat_7' => 1_400_000,
            ])
            ->assertOk()
            ->assertJsonPath('data.passenger_fare.seat_7', '1400000.00');

        $this->assertDatabaseHas('passenger_fare_rates', [
            'id' => $row->id,
            'seat_7' => 1_400_000,
        ]);

        $rev = ReferencePricingRevision::query()
            ->where('revisionable_type', PassengerFareRate::class)
            ->where('revisionable_id', $row->id)
            ->first();

        $this->assertNotNull($rev);
        $this->assertSame($user->id, $rev->user_id);
        $this->assertSame((string) $oldSeat7, (string) ($rev->snapshot['seat_7'] ?? null));
    }

    public function test_get_revisions_lists_history(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(ReferencePricingSeeder::class);
        $user = $this->actingDispatcher();

        $row = CargoFareRate::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson('/api/reference-pricing/cargo-fares/'.$row->id, [
                'route_label' => $row->route_label.' (test)',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/reference-pricing/revisions?type=cargo_fare_rate&id='.$row->id)
            ->assertOk()
            ->assertJsonPath('data.items.0.snapshot.route_label', $row->route_label);
    }
}
