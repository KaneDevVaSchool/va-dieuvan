<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUserRolesUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user->refresh();
    }

    public function test_bulk_assign_requires_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('internal_user');

        $target = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/users/roles/bulk-update', [
                'user_ids' => [$target->id],
                'roles' => ['driver'],
                'action' => 'assign',
            ])
            ->assertForbidden();
    }

    public function test_bulk_assign_adds_roles(): void
    {
        $admin = $this->adminUser();

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users/roles/bulk-update', [
                'user_ids' => [$u1->id, $u2->id],
                'roles' => ['driver'],
                'action' => 'assign',
            ])
            ->assertOk()
            ->assertJson([
                'data' => null,
                'message' => 'Roles updated successfully',
                'errors' => null,
            ]);

        $u1->refresh();
        $u2->refresh();
        $this->assertTrue($u1->hasRole('driver'));
        $this->assertTrue($u2->hasRole('driver'));
    }

    public function test_bulk_remove_removes_roles(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->adminUser();

        $u1 = User::factory()->create();
        $u1->assignRole('driver');

        $this->actingAs($admin)
            ->postJson('/api/v1/users/roles/bulk-update', [
                'user_ids' => [$u1->id],
                'roles' => ['driver'],
                'action' => 'remove',
            ])
            ->assertOk();

        $u1->refresh();
        $this->assertFalse($u1->hasRole('driver'));
    }

    public function test_validation_rejects_unknown_role(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users/roles/bulk-update', [
                'user_ids' => [$target->id],
                'roles' => ['not_a_real_role'],
                'action' => 'assign',
            ])
            ->assertStatus(422);
    }
}
