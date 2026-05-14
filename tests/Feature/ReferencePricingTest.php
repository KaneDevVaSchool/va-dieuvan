<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferencePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_pricing_requires_auth(): void
    {
        $this->getJson('/api/reference-pricing')->assertUnauthorized();
    }

    public function test_reference_pricing_returns_structure(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->actingAs($user);

        $res = $this->getJson('/api/reference-pricing');
        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'passenger_fares',
                'cargo_fares',
                'notes',
            ],
        ]);
    }
}
