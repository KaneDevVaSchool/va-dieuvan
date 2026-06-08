<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_nav_badges_returns_200_for_dispatcher(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->actingAs($user);

        $this->getJson('/api/nav/badges')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pending_dispatch_requests',
                    'cargo_sla_breaches',
                    'notifications_unread',
                ],
            ]);
    }

    public function test_nav_badges_does_not_500_when_a_policy_permission_row_is_missing(): void
    {
        $this->seed(RbacSeeder::class);
        Permission::query()->where('name', 'request.paper.manage')->delete();

        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->actingAs($user);

        $this->getJson('/api/nav/badges')->assertOk();
    }
}
