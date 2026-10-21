<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottlePerRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_throttles_do_not_share_one_bucket_across_routes(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $this->actingAs($user, 'sanctum');

        // GET /api/user: throttle:120,1. Trước đây 40 lần gọi này làm route throttle:30,1 bị 429.
        for ($i = 0; $i < 40; $i++) {
            $this->getJson('/api/user')->assertOk();
        }
        $this->postJson('/api/impersonate/leave')->assertUnprocessable();
    }

    public function test_each_route_still_enforces_its_own_limit(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        // POST /api/impersonate/leave: throttle:30,1 → request thứ 31 bị chặn.
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/impersonate/leave')->assertUnprocessable();
        }
        $this->postJson('/api/impersonate/leave')->assertStatus(429);
    }
}
