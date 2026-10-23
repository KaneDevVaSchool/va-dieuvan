<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\HrEmployee;
use App\Models\User;
use App\Services\Hr\UserWorkInfoService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserWorkInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    private function importedDriver(): User
    {
        $user = User::factory()->create(['name' => 'Nguyễn Huy Sang', 'employee_code' => 'VA011384', 'is_active' => true]);
        $user->assignRole('driver');
        $department = Department::create(['name' => 'Phòng Vận hành', 'code' => 'HV_PVH']);
        $branch = DB::table('hr_branches')->insertGetId(['code' => 'VM_THVM_TB', 'name' => 'Tiểu học Việt Mỹ - Tân Bình']);
        $job = DB::table('hr_job_titles')->insertGetId(['code' => 'DRIVER', 'name' => 'Nhân viên Lái xe']);
        HrEmployee::create([
            'employee_code' => 'VA011384', 'user_id' => $user->id, 'name' => $user->name,
            'department_id' => $department->id, 'hr_branch_id' => $branch, 'hr_job_title_id' => $job,
            'organization_labels' => ['department' => 'Phòng Vận hành', 'division' => 'Lái xe', 'branch' => 'Tiểu học Việt Mỹ - Tân Bình'],
        ]);

        return $user;
    }

    public function test_user_endpoint_displays_imported_work_info_without_cms(): void
    {
        $user = $this->importedDriver();
        $this->actingAs($user)->getJson('/api/user')->assertOk()
            ->assertJsonMissingPath('cms_user_info')
            ->assertJsonPath('work_info.code', 'VA011384')
            ->assertJsonPath('work_info.department_name', 'Phòng Vận hành')
            ->assertJsonPath('work_info.unit_name', 'Lái xe')
            ->assertJsonPath('work_info.headquarter_name', 'Tiểu học Việt Mỹ - Tân Bình')
            ->assertJsonPath('work_info.working_place', 'Tiểu học Việt Mỹ - Tân Bình')
            ->assertJsonPath('work_info.position_name', 'Nhân viên Lái xe');
    }

    public function test_personnel_has_no_external_database_dependency_or_sync_command(): void
    {
        $user = $this->importedDriver();
        $this->assertArrayNotHasKey('cms', config('database.connections'));
        $this->assertArrayNotHasKey('cms:sync-users', \Illuminate\Support\Facades\Artisan::all());
        $response = $this->actingAs($user)->getJson('/api/user')->assertOk()
            ->assertJsonPath('work_info.department_name', 'Phòng Vận hành')
            ->assertJsonPath('work_info.headquarter_name', 'Tiểu học Việt Mỹ - Tân Bình')
            ->assertJsonPath('work_info.position_name', 'Nhân viên Lái xe')
            ->assertJsonPath('work_info.working_place', 'Tiểu học Việt Mỹ - Tân Bình')
            ->assertJsonMissingPath('cms_user_info');
        $this->assertArrayNotHasKey('bank_account', $response->json('work_info'));
    }

    public function test_profile_update_response_keeps_imported_work_info(): void
    {
        $user = $this->importedDriver();
        $this->actingAs($user)->patchJson('/api/user', ['phone' => '0901234567'], ['Idempotency-Key' => 'update-hr-work-info'])
            ->assertOk()->assertJsonPath('work_info.department_name', 'Phòng Vận hành')
            ->assertJsonPath('work_info.phone', '0901234567')
            ->assertJsonPath('work_info.headquarter_name', 'Tiểu học Việt Mỹ - Tân Bình');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '0901234567']);
        $this->assertDatabaseHas('hr_employees', ['user_id' => $user->id, 'phone' => '0901234567']);
    }

    public function test_user_without_hr_has_blank_work_info_and_unresolved_hr_labels_are_preserved(): void
    {
        $user = User::factory()->create(['employee_code' => 'LOCAL']);
        $service = app(UserWorkInfoService::class);
        $department = Department::create(['name' => 'Legacy department', 'code' => 'OLD']);
        $user->update(['department_id' => $department->id]);
        $info = $service->forUser($user);
        $this->assertNull($info['department_name']);
        $this->assertNull($info['working_place']);
        $this->assertNull($info['code']);
        $this->actingAs($user)->getJson('/api/user')->assertOk()
            ->assertJsonPath('work_info.code', null)
            ->assertJsonPath('work_info.department_name', null)
            ->assertJsonPath('work_info.headquarter_name', null)
            ->assertJsonPath('work_info.working_place', null);
        HrEmployee::create(['employee_code' => 'LOCAL', 'name' => $user->name, 'user_id' => $user->id,
            'organization_labels' => ['department' => 'Imported department', 'branch' => 'Unresolved campus', 'job' => 'Imported job']]);
        $info = $service->forUser($user->fresh());
        $this->assertSame('Imported department', $info['department_name']);
        $this->assertSame('Unresolved campus', $info['headquarter_name']);
        $this->assertSame('Unresolved campus', $info['working_place']);
        $this->assertSame('Imported job', $info['position_name']);
    }

    public function test_manager_resolution_does_not_fall_back_to_legacy_user_department(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $head->assignRole('department_head');
        $department = Department::create(['code' => 'OLD', 'name' => 'Legacy department', 'manager_user_id' => $head->id]);
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->assertNull(app(\App\Services\Hr\EmployeeManagerResolver::class)->resolve($user));
    }

    public function test_profile_cannot_change_hr_managed_fields_or_legacy_personal_fields(): void
    {
        $user = $this->importedDriver();
        $this->actingAs($user)->patchJson('/api/user', [
            'employee_code' => 'REPLACED', 'department_name' => 'Other', 'bank_account' => '123',
        ], ['Idempotency-Key' => 'reject-hr-field-change'])
            ->assertUnprocessable()->assertJsonValidationErrors(['employee_code', 'department_name', 'bank_account']);
        $this->assertDatabaseHas('hr_employees', ['user_id' => $user->id, 'employee_code' => 'VA011384']);
    }

    public function test_search_and_admin_list_use_linked_hr_fields(): void
    {
        $user = $this->importedDriver();
        // A stale field on the account must not win over linked HR data.
        $user->update(['employee_code' => 'OLD-CODE']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->getJson('/api/users/for-dispatch-form?q=Sang')->assertOk()
            ->assertJsonPath('data.0.id', $user->id)
            ->assertJsonPath('data.0.employee_code', 'VA011384')
            ->assertJsonPath('data.0.department_name', 'Phòng Vận hành')
            ->assertJsonPath('data.0.unit_name', 'Lái xe');
        foreach (['10', 'all'] as $perPage) {
            $this->getJson('/api/admin/users?q=Sang&per_page='.$perPage)->assertOk()
                ->assertJsonPath('data.items.0.id', $user->id)
                ->assertJsonPath('data.items.0.employee_code', 'VA011384')
                ->assertJsonPath('data.items.0.department_name', 'Phòng Vận hành')
                ->assertJsonPath('data.items.0.position_name', 'Nhân viên Lái xe');
        }
    }
}
