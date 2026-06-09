<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
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

    public function test_nav_badges_returns_200_for_admin_with_staff_request_scope(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        DispatchRequest::query()->create([
            'requester_id' => $user->id,
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addDay(),
            'status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
            'dispatch_request_template_id' => 1,
        ]);

        $this->actingAs($user);

        $this->getJson('/api/nav/badges')->assertOk();
    }
}
