<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_add_user_with_role(): void
    {
        $role = Role::query()->where('name', 'dispatcher')->firstOrFail();

        $res = $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'name' => '  Nguyễn Văn B ',
            'email' => ' NguyenVanB@Gmail.com ',
            'employee_code' => 'NGOAI001',
            'role_id' => $role->id,
        ])->assertCreated();

        $res->assertJsonPath('data.user.email', 'nguyenvanb@gmail.com')
            ->assertJsonPath('data.user.name', 'Nguyễn Văn B')
            ->assertJsonPath('data.user.primary_role_name', 'dispatcher');

        $user = User::query()->where('email', 'nguyenvanb@gmail.com')->firstOrFail();
        $this->assertTrue($user->hasRole('dispatcher'));
        $this->assertTrue((bool) $user->is_active);
        $this->assertSame(User::SOURCE_MANUAL, $user->source);
        $this->assertTrue(AuditLog::query()->where('event', 'user.created_manually')->exists());
    }

    public function test_role_is_optional(): void
    {
        $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'name' => 'Người ngoài CMS',
            'email' => 'ngoai@example.com',
        ])->assertCreated();

        $this->assertCount(0, User::query()->where('email', 'ngoai@example.com')->firstOrFail()->roles);
    }

    public function test_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'trung@vaschools.edu.vn']);

        $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'name' => 'Trùng',
            'email' => 'TRUNG@vaschools.edu.vn',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_admin_cannot_create_superadmin_and_user_is_rolled_back(): void
    {
        $super = Role::query()->where('name', config('permission.superadmin_role', 'superadmin'))->firstOrFail();

        $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'name' => 'Leo thang',
            'email' => 'leothang@example.com',
            'role_id' => $super->id,
        ])->assertForbidden();

        $this->assertFalse(User::query()->where('email', 'leothang@example.com')->exists());
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('dispatcher');

        $this->actingAs($staff)->postJson('/api/admin/users', [
            'name' => 'X',
            'email' => 'x@example.com',
        ])->assertForbidden();
    }
}
