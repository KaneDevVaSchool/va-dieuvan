<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchStaffDeptHeadSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_staff_can_search_dept_heads_on_mng_form_endpoint(): void
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $head = User::factory()->create([
            'is_active' => true,
            'name' => 'Tran Truong BP',
            'email' => 'truong.bp.staff@example.test',
        ]);
        $head->assignRole('department_head');

        $this->actingAs($dispatcher);

        $this->getJson('/api/users/dept-heads?q=Truong')
            ->assertOk()
            ->assertJsonPath('data.0.id', $head->id);
    }

    public function test_portal_user_cannot_use_dispatch_staff_dept_head_endpoint(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);

        $this->actingAs($requester);

        $this->getJson('/api/users/dept-heads?q=ab')
            ->assertForbidden();
    }
}
