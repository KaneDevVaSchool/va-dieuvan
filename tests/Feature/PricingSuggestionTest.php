<?php

namespace Tests\Feature;

use App\Models\CargoFareRate;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');

        return $user;
    }

    public function test_suggest_returns_cargo_fares_when_enabled(): void
    {
        config(['dispatch.pricing_suggest_enabled' => true]);

        CargoFareRate::query()->create([
            'sort_order' => 1,
            'route_code' => 'HN-HCM',
            'route_label' => 'Hà Nội đi Hồ Chí Minh',
            'distance_km' => 1700,
            'one_crate_50_40_50' => 100000,
            'crates_2_to_5_50_40_50' => 200000,
            'van_500kg' => 2500000,
            'van_1000kg' => 3000000,
            'van_2000kg' => 4000000,
            'loading_assist_per_point' => 50000,
            'waiting_fee_per_hour' => 80000,
        ]);

        $user = $this->staffUser();

        $response = $this->actingAs($user)->getJson('/api/reference-pricing/suggest?'.http_build_query([
            'trip_type' => 'cargo',
            'origin' => 'Hà Nội',
            'destination' => 'Hồ Chí Minh',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.enabled', true);
        $this->assertNotEmpty($response->json('data.suggestions'));
    }

    public function test_suggest_disabled_returns_empty(): void
    {
        config(['dispatch.pricing_suggest_enabled' => false]);

        $user = $this->staffUser();

        $response = $this->actingAs($user)->getJson('/api/reference-pricing/suggest?trip_type=point_to_point');

        $response->assertOk();
        $response->assertJsonPath('data.enabled', false);
        $response->assertJsonPath('data.suggestions', []);
    }
}
