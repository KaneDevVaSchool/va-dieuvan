<?php

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Driver;
use App\Models\HrDivision;
use App\Models\HrEmployee;
use App\Models\User;
use App\Services\Hr\EmployeeManagerResolver;
use App\Services\Hr\HrMasterDataImportService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportHrMasterDataCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hr.driver_links' => []]);
        $this->seed(RbacSeeder::class);
        $this->path = tempnam(sys_get_temp_dir(), 'hr-master-');
        $this->workbook();
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function workbook(?callable $change = null): void
    {
        $sheets = [
            'CompanyLists' => [['Mã', 'Tên tiếng Việt *'], ['HV', 'Company']],
            'BranchLists' => [['Mã', 'Tên cơ sở *', 'Công ty'], ['HV_BT', 'Campus', 'Company']],
            'DepartmentLists' => [['Mã phần mềm', 'Phòng ban *', 'Pháp nhân', 'Email quản lý'], ['HV_IT', 'IT', 'Company', 'head@example.test']],
            'DivisionLists' => [['Mã phần mềm', 'Tên bộ phận *', 'Pháp nhân', 'Phòng ban *', 'Email quản lý'], ['HV_IT_DEV', 'Dev', 'Company', 'IT', 'lead@example.test']],
            'LevelLists' => [['Số cấp *', 'Tên cấp *'], [1, 'Staff']],
            'JobTitleLists' => [['Mã chức danh', 'Tên chức danh *'], ['HV_DEV', 'Developer']],
            'EmployeeList' => [
                ['Mã nhân viên 1 *', 'Họ Tên *', 'Email Công Ty', 'Số Điện Thoại', 'Pháp Nhân Chính', 'Mã Đơn Vị', 'Phòng ban *', 'Bộ Phận', 'Cơ Sở', 'Phân Loại Cấp Bậc', 'Mã Chức Danh', 'Chức Vụ Chính *', 'Trạng thái nhân sự'],
                ['VA01', 'Head', 'head@example.test', '0900000001', 'Company', 'HV_IT', 'IT', '', 'Campus', 'Staff', 'HV_DEV', 'Developer', 'Đang làm việc'],
                ['VA02', 'Lead', 'lead@example.test', '', 'Company', 'HV_IT', 'IT', 'Dev', 'Campus', 'Staff', 'HV_DEV', 'Developer', 'Đang làm việc'],
                ['VA03', 'Employee', 'employee@example.test', '', 'Company', 'HV_IT', 'IT', 'Dev', 'Campus', 'Staff', 'HV_DEV', 'Developer', 'Đang làm việc'],
                ['VA04', 'No email', '', '', 'Company', 'HV_IT', 'IT', '', 'Campus', 'Staff', 'HV_DEV', 'Developer', 'Đang làm việc'],
            ],
        ];
        if ($change) {
            $change($sheets);
        }
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        foreach ($sheets as $name => $rows) {
            $book->createSheet()->setTitle('Imported PROD - '.$name)->fromArray($rows);
        }
        (new Xlsx($book))->save($this->path);
        $book->disconnectWorksheets();
    }

    private function runImport(array $options = []): int
    {
        return Artisan::call('hr:import-master-data', ['file' => $this->path] + $options);
    }

    public function test_dry_run_does_not_write_even_audit_or_roles(): void
    {
        $before = AuditLog::count();
        $this->assertSame(0, $this->runImport(['--dry-run' => true, '--grant-head-role' => true]), Artisan::output());
        $this->assertDatabaseCount('hr_employees', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('departments', 0);
        $this->assertSame($before, AuditLog::count());
    }

    public function test_import_is_repeatable_preserves_identity_password_roles_and_missing_email_profiles(): void
    {
        $user = User::factory()->create(['email' => 'head@example.test', 'employee_code' => null, 'is_active' => false, 'source' => 'cms']);
        $user->assignRole('dispatcher');
        $password = $user->getRawOriginal('password');
        $this->assertSame(0, $this->runImport(['--grant-head-role' => true]), Artisan::output());
        $this->assertDatabaseCount('hr_employees', 4);
        $this->assertDatabaseCount('users', 3);
        $this->assertNull(HrEmployee::where('employee_code', 'VA04')->firstOrFail()->user_id);
        $this->assertSame('VA01', $user->fresh()->employee_code);
        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
        $this->assertSame('cms', $user->fresh()->source);
        $this->assertFalse((bool) $user->fresh()->is_active);
        $this->assertTrue($user->fresh()->hasAllRoles(['dispatcher', 'department_head']));
        $this->assertSame($user->id, Department::firstOrFail()->manager_user_id);
        $ids = HrEmployee::pluck('id', 'employee_code')->all();
        $updates = AuditLog::where('event', 'hr.master_data_updated')->count();
        $this->assertSame(0, $this->runImport(['--grant-head-role' => true]), Artisan::output());
        $this->assertSame($ids, HrEmployee::pluck('id', 'employee_code')->all());
        $this->assertSame($updates, AuditLog::where('event', 'hr.master_data_updated')->count());
        $this->assertDatabaseCount('users', 3);
    }

    public function test_import_does_not_grant_roles_without_option(): void
    {
        $this->assertSame(0, $this->runImport(), Artisan::output());
        $this->assertDatabaseCount('model_has_roles', 0);
        $requester = User::where('employee_code', 'VA03')->firstOrFail();
        $this->assertNull(app(EmployeeManagerResolver::class)->resolve($requester));
    }

    public function test_conflicting_employee_code_and_email_abort_entire_import(): void
    {
        User::factory()->create(['email' => 'old@example.test', 'employee_code' => 'VA03']);
        User::factory()->create(['email' => 'employee@example.test', 'employee_code' => null]);
        $this->assertSame(1, $this->runImport(), Artisan::output());
        $this->assertDatabaseCount('hr_companies', 0);
        $this->assertDatabaseCount('hr_employees', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->workbook(function (&$sheets) {
            $sheets['EmployeeList'][3][2] = 'HEAD@example.test';
        });
        $this->assertSame(1, $this->runImport(), Artisan::output());
        $this->assertDatabaseCount('hr_employees', 0);
    }

    public function test_identical_catalog_rows_are_coalesced_but_conflicting_codes_fail(): void
    {
        $this->workbook(function (&$sheets) {
            $sheets['JobTitleLists'][] = $sheets['JobTitleLists'][1];
        });
        $plan = app(HrMasterDataImportService::class)->import($this->path, true);
        $this->assertSame([], $plan['errors']);
        $this->assertSame(1, $plan['counts']['jobs']);
        $this->workbook(function (&$sheets) {
            $sheets['JobTitleLists'][] = ['HV_DEV', 'Different title'];
        });
        $this->assertSame(1, $this->runImport(), Artisan::output());
        $this->assertDatabaseCount('hr_job_titles', 0);
    }

    public function test_same_branch_name_is_disambiguated_by_company(): void
    {
        $this->workbook(function (&$sheets) {
            $sheets['CompanyLists'][] = ['OTHER', 'Other company'];
            $sheets['BranchLists'][] = ['OTHER_BT', 'Campus', 'Other company'];
        });
        $this->assertSame(0, $this->runImport(), Artisan::output());
        $branchId = \Illuminate\Support\Facades\DB::table('hr_branches')->where('code', 'HV_BT')->value('id');
        $this->assertSame($branchId, HrEmployee::where('employee_code', 'VA03')->firstOrFail()->hr_branch_id);
    }

    public function test_failure_after_all_writes_rolls_back_profiles_users_organizations_and_roles(): void
    {
        $before = AuditLog::count();
        AuditLog::creating(function ($log) {
            if ($log->event === 'hr.master_data_imported') {
                throw new \RuntimeException('Simulated audit failure');
            }
        });
        try {
            $this->assertSame(1, $this->runImport(['--grant-head-role' => true]), Artisan::output());
            foreach (['hr_companies', 'hr_employees', 'hr_divisions', 'departments', 'users', 'model_has_roles'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
            $this->assertSame($before, AuditLog::count());
        } finally {
            AuditLog::flushEventListeners();
        }
    }

    public function test_unresolved_references_are_reported_and_preserved_and_strict_blocks_writes(): void
    {
        $this->workbook(function (&$sheets) {
            $sheets['EmployeeList'][3][8] = 'Campus alias';
            $sheets['EmployeeList'][3][10] = 'UNKNOWN';
        });
        $this->assertSame(1, $this->runImport(['--strict' => true]), Artisan::output());
        $this->assertDatabaseCount('hr_employees', 0);
        $this->assertSame(0, $this->runImport(['--branch-alias' => ['Campus alias=Campus']]), Artisan::output());
        $employee = HrEmployee::where('employee_code', 'VA03')->firstOrFail();
        $this->assertNotNull($employee->hr_branch_id);
        $this->assertNull($employee->hr_job_title_id);
        $this->assertSame('UNKNOWN', $employee->organization_labels['job_code']);
    }

    public function test_resolver_prefers_division_falls_back_for_self_inactive_or_unprivileged_and_snapshots_request(): void
    {
        $this->assertSame(0, $this->runImport(['--grant-head-role' => true]), Artisan::output());
        $requester = User::where('employee_code', 'VA03')->firstOrFail();
        $head = User::where('employee_code', 'VA01')->firstOrFail();
        $lead = User::where('employee_code', 'VA02')->firstOrFail();
        $resolver = app(EmployeeManagerResolver::class);
        $this->assertSame($lead->id, $resolver->resolve($requester)->id);
        $this->assertSame($head->id, $resolver->resolve($lead)->id);
        $lead->update(['is_active' => false]);
        $this->assertSame($head->id, $resolver->resolve($requester)->id);
        $lead->update(['is_active' => true]);
        $lead->removeRole('department_head');
        $this->assertSame($head->id, $resolver->resolve($requester)->id);
        $lead->assignRole('department_head');

        $this->actingAs($requester)->getJson('/api/portal/users/dept-heads?suggested=1')
            ->assertOk()->assertJsonPath('data.0.id', $lead->id);
        $response = $this->postJson('/api/portal/dispatch-requests', [
            'trip_type' => 'business', 'depart_at' => now()->addDays(30)->toIso8601String(),
            'wizard_snapshot' => ['form' => ['purpose' => 'Meeting']],
        ], ['Idempotency-Key' => 'hr-default-head']);
        $response->assertCreated();
        $requestId = $response->json('data.id');
        HrDivision::query()->update(['manager_user_id' => $head->id]);
        $this->assertDatabaseHas('dispatch_requests', ['id' => $requestId, 'assigned_dept_head_id' => $lead->id]);
        $this->assertSame($head->id, $resolver->resolve($requester)->id);
    }

    public function test_explicit_approver_is_preserved_and_door_to_door_does_not_auto_assign(): void
    {
        $this->assertSame(0, $this->runImport(['--grant-head-role' => true]), Artisan::output());
        $requester = User::where('employee_code', 'VA03')->firstOrFail();
        $head = User::where('employee_code', 'VA01')->firstOrFail();
        $this->assertSame($head->id, \App\Support\DispatchRequestDeptHeadAssignment::resolveForRequester($head->id, $requester->id, 'business'));
        $this->assertNull(\App\Support\DispatchRequestDeptHeadAssignment::resolveForRequester(null, $requester->id, 'door_to_door'));
    }

    public function test_reimport_does_not_reassign_a_profile_to_another_user(): void
    {
        $this->assertSame(0, $this->runImport(), Artisan::output());
        $employee = HrEmployee::where('employee_code', 'VA03')->firstOrFail();
        $user = $employee->user;
        $user->update(['email' => 'renamed@example.test', 'employee_code' => null]);
        User::factory()->create(['email' => 'employee@example.test']);
        $this->assertSame(1, $this->runImport(), Artisan::output());
        $this->assertSame($user->id, $employee->fresh()->user_id);
    }

    public function test_links_only_configured_existing_driver_and_keeps_test_profile_and_driver_details(): void
    {
        config(['hr.driver_links' => ['VA03' => 'Employee']]);
        $user = User::factory()->create(['email' => 'employee@example.test', 'employee_code' => null, 'name' => 'Old user name']);
        $user->assignRole('internal_user');
        $driver = Driver::create(['full_name' => 'Employee', 'phone' => '0901234567', 'license_class' => 'D']);
        $testUser = User::factory()->create();
        $testUser->assignRole('driver');
        $testDriver = Driver::create(['full_name' => 'Tran Minh Quang', 'user_id' => $testUser->id]);
        $before = $testDriver->fresh()->getAttributes();
        $this->assertSame(0, $this->runImport(['--dry-run' => true]), Artisan::output());
        $this->assertNull($driver->fresh()->user_id);
        $this->assertFalse($user->fresh()->hasRole('driver'));

        $this->assertSame(0, $this->runImport(), Artisan::output());
        $this->assertSame($user->id, $driver->fresh()->user_id);
        $this->assertSame('0901234567', $driver->fresh()->phone);
        $this->assertSame('D', $driver->fresh()->license_class);
        $this->assertSame($before, $testDriver->fresh()->getAttributes());
        $this->assertTrue($user->fresh()->hasAllRoles(['internal_user', 'driver']));
        $this->assertDatabaseCount('drivers', 2);
        $this->assertSame(0, $this->runImport(), Artisan::output());
        $this->assertSame($user->id, $driver->fresh()->user_id);
        $this->assertDatabaseCount('drivers', 2);
    }

    public function test_new_driver_user_is_created_and_linked_without_creating_driver_profiles(): void
    {
        config(['hr.driver_links' => ['VA03' => 'Employee']]);
        $driver = Driver::create(['full_name' => 'Employee']);
        $this->assertSame(0, $this->runImport(), Artisan::output());
        $user = User::where('employee_code', 'VA03')->firstOrFail();
        $this->assertSame($user->id, $driver->fresh()->user_id);
        $this->assertTrue($user->hasRole('driver'));
        $this->assertDatabaseCount('drivers', 1);
    }

    public function test_skip_conflicts_skips_whole_non_driver_employee_but_never_driver_conflicts(): void
    {
        User::factory()->create(['employee_code' => 'VA01', 'email' => 'other@example.test']);
        User::factory()->create(['employee_code' => null, 'email' => 'head@example.test']);
        $this->assertSame(0, $this->runImport(['--skip-user-conflicts' => true]), Artisan::output());
        $this->assertDatabaseMissing('hr_employees', ['employee_code' => 'VA01']);
        $this->assertDatabaseCount('hr_employees', 3);
        config(['hr.driver_links' => ['VA01' => 'Head']]);
        Driver::create(['full_name' => 'Head']);
        $this->assertSame(1, $this->runImport(['--skip-user-conflicts' => true]), Artisan::output());
        $this->assertDatabaseMissing('hr_employees', ['employee_code' => 'VA01']);
    }

    public function test_driver_link_must_not_replace_an_existing_user_link(): void
    {
        config(['hr.driver_links' => ['VA03' => 'Employee']]);
        $original = User::factory()->create();
        $driver = Driver::create(['full_name' => 'Employee', 'user_id' => $original->id]);
        $this->assertSame(1, $this->runImport(['--skip-user-conflicts' => true]), Artisan::output());
        $this->assertSame($original->id, $driver->fresh()->user_id);
        $this->assertDatabaseCount('hr_employees', 0);
    }

    public function test_missing_core_driver_profile_stops_import_instead_of_creating_one(): void
    {
        config(['hr.driver_links' => ['VA03' => 'Employee']]);
        $this->assertSame(1, $this->runImport(), Artisan::output());
        $this->assertDatabaseCount('drivers', 0);
        $this->assertDatabaseCount('hr_employees', 0);
    }
}
