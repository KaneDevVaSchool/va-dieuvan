<?php

namespace App\Services\Hr;

use App\Models\Department;
use App\Models\Driver;
use App\Models\HrDivision;
use App\Models\HrEmployee;
use App\Models\Role;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;

class HrMasterDataImportService
{
    private const SHEETS = [
        'companies' => ['CompanyLists', ['code' => 'Mã', 'name' => 'Tên tiếng Việt *']],
        'branches' => ['BranchLists', ['code' => 'Mã', 'name' => 'Tên cơ sở *', 'company' => 'Công ty']],
        'departments' => ['DepartmentLists', ['code' => 'Mã phần mềm', 'name' => 'Phòng ban *', 'company' => 'Pháp nhân', 'manager_email' => 'Email quản lý']],
        'divisions' => ['DivisionLists', ['code' => 'Mã phần mềm', 'name' => 'Tên bộ phận *', 'company' => 'Pháp nhân', 'department' => 'Phòng ban *', 'manager_email' => 'Email quản lý']],
        'levels' => ['LevelLists', ['code' => 'Số cấp *', 'name' => 'Tên cấp *']],
        'jobs' => ['JobTitleLists', ['code' => 'Mã chức danh', 'name' => 'Tên chức danh *']],
        'employees' => ['EmployeeList', [
            'code' => 'Mã nhân viên 1 *', 'name' => 'Họ Tên *', 'email' => 'Email Công Ty',
            'phone' => 'Số Điện Thoại', 'company' => 'Pháp Nhân Chính', 'department_code' => 'Mã Đơn Vị',
            'department' => 'Phòng ban *', 'division' => 'Bộ Phận', 'branch' => 'Cơ Sở',
            'level' => 'Phân Loại Cấp Bậc', 'job_code' => 'Mã Chức Danh', 'job' => 'Chức Vụ Chính *',
            'status' => 'Trạng thái nhân sự',
        ]],
    ];

    /** Planning performs reads only, including for --dry-run. Issues retain Excel row numbers. */
    public function plan(string $path, array $branchAliases = [], bool $skipUserConflicts = false): array
    {
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(array_map(fn ($spec) => 'Imported PROD - '.$spec[0], self::SHEETS));
        $book = $reader->load($path);
        $plan = ['data' => [], 'errors' => [], 'warnings' => [], 'counts' => [], 'users' => [], 'driver_links' => [], 'skipped_employees' => []];
        try {
            foreach (self::SHEETS as $key => [$suffix, $columns]) {
                $title = 'Imported PROD - '.$suffix;
                $sheet = $book->getSheetByName($title);
                if (! $sheet) {
                    throw new RuntimeException('Thiếu sheet: '.$title);
                }
                $rows = $sheet->toArray(null, false, false, false);
                $headers = array_map($this->text(...), array_shift($rows));
                $indexes = [];
                foreach ($columns as $field => $header) {
                    $index = array_search($header, $headers, true);
                    if ($index === false) {
                        throw new RuntimeException("{$title}: thiếu cột {$header}");
                    }
                    $indexes[$field] = $index;
                }
                $records = [];
                $sourceRows = [];
                foreach ($rows as $offset => $row) {
                    if (! array_filter($row, fn ($value) => $this->text($value) !== '')) {
                        continue;
                    }
                    $record = ['row' => $offset + 2];
                    foreach ($indexes as $field => $index) {
                        $record[$field] = $this->text($row[$index] ?? null);
                    }
                    if ($key !== 'levels') {
                        $record['code'] = mb_strtoupper($record['code']);
                    }
                    foreach (['email', 'manager_email'] as $field) {
                        if (isset($record[$field])) {
                            $record[$field] = mb_strtolower($record[$field]);
                            if ($record[$field] !== '' && ! filter_var($record[$field], FILTER_VALIDATE_EMAIL)) {
                                $this->issue($plan, 'errors', $key, $record, "Email không hợp lệ: {$field}");
                            }
                        }
                    }
                    if ($record['code'] === '' || $record['name'] === '') {
                        $this->issue($plan, 'errors', $key, $record, 'Thiếu mã hoặc tên');
                    }
                    if (isset($records[$record['code']])) {
                        $identical = $sourceRows[$record['code']] === array_map($this->text(...), $row);
                        $this->issue($plan, $identical && $key !== 'employees' ? 'warnings' : 'errors', $key, $record,
                            $identical && $key !== 'employees' ? 'Dòng danh mục trùng hoàn toàn; dùng bản ghi đầu tiên' : 'Trùng mã trong file');

                        continue;
                    }
                    $sourceRows[$record['code']] = array_map($this->text(...), $row);
                    $records[$record['code']] = $record;
                }
                $plan['data'][$key] = $records;
                $plan['counts'][$key] = count($records);
            }
        } finally {
            $book->disconnectWorksheets();
        }

        $data = &$plan['data'];
        foreach (['companies', 'levels'] as $key) {
            $this->checkUniqueNames($plan, $key);
        }
        $companies = array_column($data['companies'], 'code', 'name');
        $branches = collect($data['branches'])->groupBy('name');
        foreach ($branches as $items) {
            if ($items->pluck('company')->unique()->count() !== $items->count()) {
                $this->issue($plan, 'errors', 'branches', $items->last(), 'Trùng tên cơ sở trong cùng pháp nhân');
            }
        }
        $levels = array_column($data['levels'], 'code', 'name');
        $departmentNames = [];
        foreach ($data['departments'] as &$row) {
            $row['company_code'] = $companies[$row['company']] ?? null;
            if (! $row['company_code']) {
                $this->issue($plan, 'errors', 'departments', $row, 'Pháp nhân không tồn tại');
            }
            $key = $row['company'].'|'.$row['name'];
            if (isset($departmentNames[$key])) {
                $this->issue($plan, 'errors', 'departments', $row, 'Trùng tên phòng ban trong pháp nhân');
            }
            $departmentNames[$key] = $row['code'];
        }
        unset($row);
        $divisionNames = [];
        foreach ($data['divisions'] as &$row) {
            $row['department_code'] = $departmentNames[$row['company'].'|'.$row['department']] ?? null;
            if (! $row['department_code']) {
                $this->issue($plan, 'errors', 'divisions', $row, 'Phòng ban không tồn tại');
            }
            $key = $row['department_code'].'|'.$row['name'];
            if (isset($divisionNames[$key])) {
                $this->issue($plan, 'errors', 'divisions', $row, 'Trùng tên bộ phận trong phòng ban');
            }
            $divisionNames[$key] = $row['code'];
        }
        unset($row);
        foreach ($branchAliases as $from => $to) {
            if (! $branches->has($to) && ! isset($data['branches'][$to])) {
                throw new RuntimeException('Cơ sở đích của alias không tồn tại: '.$to);
            }
        }
        $employeeEmails = [];
        foreach ($data['employees'] as &$row) {
            $row['department_code'] = mb_strtoupper($row['department_code']);
            $row['job_code'] = mb_strtoupper($row['job_code']);
            $row['company_code'] = $companies[$row['company']] ?? null;
            $department = $data['departments'][$row['department_code']] ?? null;
            if ($department && ($department['company'] !== $row['company'] || $department['name'] !== $row['department'])) {
                $this->issue($plan, 'errors', 'employees', $row, 'Mã đơn vị không khớp pháp nhân/phòng ban');
            }
            $row['division_code'] = $divisionNames[$row['department_code'].'|'.$row['division']] ?? null;
            $branchName = $branchAliases[$row['branch']] ?? $row['branch'];
            $branchMatches = $branches->get($branchName, collect());
            $companyBranches = $branchMatches->where('company', $row['company']);
            $branch = $companyBranches->count() === 1 ? $companyBranches->first() : ($branchMatches->count() === 1 ? $branchMatches->first() : null);
            $row['branch_code'] = $branch['code'] ?? (isset($branchAliases[$row['branch']]) ? ($data['branches'][$branchName]['code'] ?? null) : null);
            $row['level_code'] = $levels[$row['level']] ?? null;
            foreach (['company' => $row['company_code'], 'department' => $department, 'division' => $row['division_code'], 'branch' => $row['branch_code'], 'level' => $row['level_code'], 'job' => $data['jobs'][$row['job_code']] ?? null] as $field => $resolved) {
                if (! $resolved && ($row[$field] !== '' || in_array($field, ['company', 'department']))) {
                    $this->issue($plan, 'warnings', 'employees', $row, "Chưa xác định {$field}: ".$row[$field]);
                }
            }
            if ($row['email'] !== '') {
                if (isset($employeeEmails[$row['email']])) {
                    $this->issue($plan, 'errors', 'employees', $row, 'Email công ty trùng trong file');
                }
                $employeeEmails[$row['email']] = $row['code'];
            } else {
                $this->issue($plan, 'warnings', 'employees', $row, 'Thiếu email công ty; chỉ liên kết tài khoản nếu khớp mã NV');
            }
        }
        unset($row);
        foreach (['departments', 'divisions'] as $key) {
            foreach ($data[$key] as $row) {
                if ($row['manager_email'] === '' || ! isset($employeeEmails[$row['manager_email']])) {
                    $this->issue($plan, 'warnings', $key, $row, 'Thiếu quản lý hoặc email quản lý không có trong danh sách nhân viên');
                }
            }
        }
        $this->matchUsers($plan, $skipUserConflicts);
        $this->planDriverLinks($plan);

        return $plan;
    }

    private function checkUniqueNames(array &$plan, string $key): void
    {
        $seen = [];
        foreach ($plan['data'][$key] as $row) {
            if (isset($seen[$row['name']])) {
                $this->issue($plan, 'errors', $key, $row, 'Trùng tên, không thể đối chiếu tham chiếu bằng tên');
            }
            $seen[$row['name']] = true;
        }
    }

    private function matchUsers(array &$plan, bool $skipUserConflicts): void
    {
        $users = User::query()->get(['id', 'email', 'employee_code']);
        $byCode = $users->filter(fn ($u) => $this->text($u->employee_code) !== '')
            ->groupBy(fn ($u) => mb_strtoupper($this->text($u->employee_code)));
        $byEmail = $users->groupBy(fn ($u) => mb_strtolower($this->text($u->email)));
        $linked = HrEmployee::query()->whereNotNull('user_id')->pluck('employee_code', 'user_id');
        $claimed = [];
        foreach ($plan['data']['employees'] as $code => $row) {
            $codes = $byCode->get($code, collect());
            $emails = $row['email'] !== '' ? $byEmail->get($row['email'], collect()) : collect();
            if ($codes->count() > 1 || $emails->count() > 1 || ($codes->isNotEmpty() && $emails->isNotEmpty() && $codes->first()->id !== $emails->first()->id)) {
                $this->userConflict($plan, $row, 'Mã NV/email khớp nhiều tài khoản hoặc trỏ tới hai tài khoản khác nhau', $skipUserConflicts);

                continue;
            }
            $user = $codes->first() ?? $emails->first();
            $profile = HrEmployee::query()->where('employee_code', $code)->first();
            if ($profile?->user_id && $profile->user_id !== $user?->id) {
                $this->userConflict($plan, $row, 'Liên kết tài khoản của hồ sơ đã thay đổi; cần xử lý thủ công', $skipUserConflicts);

                continue;
            }
            if ($user && (($this->text($user->employee_code) !== '' && mb_strtoupper($this->text($user->employee_code)) !== $code)
                || (isset($linked[$user->id]) && $linked[$user->id] !== $code) || isset($claimed[$user->id]))) {
                $this->userConflict($plan, $row, 'Tài khoản đã thuộc mã nhân viên khác', $skipUserConflicts);

                continue;
            }
            if ($user && $row['email'] !== '' && mb_strtolower($this->text($user->email)) !== $row['email']) {
                $this->issue($plan, 'warnings', 'employees', $row, 'Khớp mã NV nhưng khác email: giữ email đăng nhập hiện tại');
            }
            if ($user) {
                $claimed[$user->id] = true;
            }
            $plan['users'][$code] = ['id' => $user?->id, 'action' => $user ? 'update' : ($row['email'] !== '' ? 'create' : 'profile_only')];
        }
    }

    private function userConflict(array &$plan, array $row, string $message, bool $skip): void
    {
        // Driver identity conflicts must always stop the import, even in skip mode.
        $skip = $skip && ! array_key_exists($row['code'], config('hr.driver_links', []));
        $this->issue($plan, $skip ? 'warnings' : 'errors', 'employees', $row, ($skip ? 'Bỏ qua nhân viên: ' : '').$message);
        if ($skip) {
            $plan['users'][$row['code']] = ['id' => null, 'action' => 'skip'];
            $plan['skipped_employees'][] = ['row' => $row['row'], 'code' => $row['code'], 'reason' => $message];
        }
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', Str::ascii($this->text($name))));
    }

    private function planDriverLinks(array &$plan): void
    {
        $drivers = Driver::query()->get();
        $claimed = [];
        foreach (config('hr.driver_links', []) as $code => $name) {
            $row = $plan['data']['employees'][$code] ?? null;
            if (! $row) {
                $plan['errors'][] = ['sheet' => 'EmployeeList', 'row' => 0, 'code' => $code, 'message' => 'Thiếu tài xế chính trong file: '.$name];

                continue;
            }
            $matches = $drivers->filter(fn ($driver) => $this->nameKey($driver->full_name) === $this->nameKey($name));
            $match = $plan['users'][$code] ?? null;
            if ($this->nameKey($row['name']) !== $this->nameKey($name) || $matches->count() !== 1) {
                $this->issue($plan, 'errors', 'employees', $row, 'Mapping tài xế phải khớp đúng một hồ sơ hiện có và tên nhân viên Excel');

                continue;
            }
            if (! $match || in_array($match['action'], ['skip', 'profile_only'])) {
                $this->issue($plan, 'errors', 'employees', $row, 'Tài xế chính chưa xác định được tài khoản đăng nhập');

                continue;
            }
            $driver = $matches->first();
            if (($driver->user_id && $driver->user_id !== $match['id'])
                || ($match['id'] && $drivers->contains(fn ($other) => $other->id !== $driver->id && $other->user_id === $match['id']))
                || isset($claimed[$driver->id])) {
                $this->issue($plan, 'errors', 'employees', $row, 'Mapping tài xế xung đột với liên kết tài khoản hiện có');

                continue;
            }
            $claimed[$driver->id] = true;
            $user = $match['id'] ? User::findOrFail($match['id']) : null;
            if ($user && $this->nameKey($user->name) !== $this->nameKey($name)) {
                $this->issue($plan, 'warnings', 'employees', $row, 'Tên tài khoản khác Excel; nối theo mã/email đã khớp và cập nhật tên theo Excel');
            }
            $plan['driver_links'][] = [
                'code' => $code, 'name' => $name, 'driver_id' => $driver->id,
                'previous_user_id' => $driver->user_id, 'user_id' => $match['id'],
                'action' => $driver->user_id ? 'keep' : 'link',
            ];
        }
    }

    /** Re-plan under a transaction so a previously reviewed plan cannot become stale. */
    public function import(string $path, bool $dryRun = true, bool $strict = false, bool $grantHeadRole = false, array $branchAliases = [], bool $skipUserConflicts = false): array
    {
        return DB::transaction(function () use ($path, $dryRun, $strict, $grantHeadRole, $branchAliases, $skipUserConflicts) {
            // Serialize concurrent imports; matching users are also locked during apply.
            if (! $dryRun) {
                DB::table('hr_employees')->lockForUpdate()->get(['id']);
                User::query()->lockForUpdate()->get(['id']);
                Driver::query()->lockForUpdate()->get(['id']);
            }
            $plan = $this->plan($path, $branchAliases, $skipUserConflicts);
            if ($grantHeadRole && ! Role::where('name', 'department_head')->where('guard_name', 'web')->exists()) {
                $plan['errors'][] = ['sheet' => 'roles', 'row' => 0, 'code' => 'department_head', 'message' => 'Thiếu role department_head; cần seed RBAC trước khi cấp quyền'];
            }
            if ($plan['driver_links'] && ! Role::where('name', 'driver')->where('guard_name', 'web')->exists()) {
                $plan['errors'][] = ['sheet' => 'roles', 'row' => 0, 'code' => 'driver', 'message' => 'Thiếu role driver; cần seed RBAC trước khi nối tài xế'];
            }
            $plan['applied'] = false;
            $plan['user_counts'] = array_count_values(array_column($plan['users'], 'action'));
            if ($plan['errors'] || ($strict && $plan['warnings']) || $dryRun) {
                return $plan;
            }
            $this->apply($plan, $grantHeadRole);
            foreach ($plan['driver_links'] as &$link) {
                $link['user_id'] = HrEmployee::where('employee_code', $link['code'])->value('user_id');
            }
            unset($link);
            $plan['applied'] = true;

            return $plan;
        });
    }

    private function apply(array $plan, bool $grantHeadRole): void
    {
        $data = $plan['data'];
        $ids = [];
        foreach (['companies' => 'hr_companies', 'branches' => 'hr_branches', 'levels' => 'hr_levels', 'jobs' => 'hr_job_titles'] as $key => $table) {
            foreach ($data[$key] as $code => $row) {
                $existing = DB::table($table)->where('code', $code)->first();
                $values = ['name' => $row['name'], 'metadata' => json_encode(array_diff_key($row, array_flip(['row', 'code', 'name'])), JSON_UNESCAPED_UNICODE)];
                if ($existing) {
                    $ids[$key][$code] = $existing->id;
                    if ($existing->name !== $values['name'] || json_decode($existing->metadata ?? '{}', true) !== json_decode($values['metadata'], true)) {
                        DB::table($table)->where('id', $existing->id)->update($values + ['updated_at' => now()]);
                    }
                } else {
                    $ids[$key][$code] = DB::table($table)->insertGetId($values + ['code' => $code, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
        foreach ($data['departments'] as $code => $row) {
            $model = Department::firstOrNew(['code' => $code]);
            $this->save($model, ['name' => $row['name'], 'hr_company_id' => $ids['companies'][$row['company_code']]]);
            $ids['departments'][$code] = $model->id;
        }
        foreach ($data['divisions'] as $code => $row) {
            $model = HrDivision::firstOrNew(['code' => $code]);
            $this->save($model, ['name' => $row['name'], 'department_id' => $ids['departments'][$row['department_code']]]);
            $ids['divisions'][$code] = $model->id;
        }
        $usersByEmail = [];
        $usersByCode = [];
        foreach ($data['employees'] as $code => $row) {
            $match = $plan['users'][$code];
            if ($match['action'] === 'skip') {
                continue;
            }
            $user = $match['id'] ? User::findOrFail($match['id']) : null;
            $departmentId = $ids['departments'][$row['department_code']] ?? null;
            if ($match['action'] === 'create') {
                $user = new User(['email' => $row['email'], 'password' => Str::random(48), 'source' => User::SOURCE_MANUAL, 'is_active' => $row['status'] === 'Đang làm việc']);
            }
            if ($user) {
                $usersByCode[$code] = $user;
                $values = ['name' => $row['name'], 'employee_code' => $code];
                // Incomplete source data must not erase manually supplemented user fields.
                if ($row['phone'] !== '') {
                    $values['phone'] = $row['phone'];
                }
                if ($departmentId !== null) {
                    $values['department_id'] = $departmentId;
                }
                $this->save($user, $values);
                if ($row['email'] !== '') {
                    $usersByEmail[$row['email']] = $user;
                }
            }
            $employee = HrEmployee::firstOrNew(['employee_code' => $code]);
            $this->save($employee, [
                'user_id' => $user?->id, 'name' => $row['name'], 'company_email' => $row['email'] ?: null,
                'phone' => $row['phone'] ?: null, 'employment_status' => $row['status'] ?: null,
                'hr_company_id' => $ids['companies'][$row['company_code']] ?? null,
                'department_id' => $departmentId,
                'hr_division_id' => $ids['divisions'][$row['division_code']] ?? null,
                'hr_branch_id' => $ids['branches'][$row['branch_code']] ?? null,
                'hr_level_id' => $ids['levels'][$row['level_code']] ?? null,
                'hr_job_title_id' => $ids['jobs'][$row['job_code']] ?? null,
                'organization_labels' => array_intersect_key($row, array_flip(['company', 'department_code', 'department', 'division', 'branch', 'level', 'job_code', 'job'])),
            ]);
        }
        if ($plan['driver_links']) {
            $driverRole = Role::where('name', 'driver')->where('guard_name', 'web')->firstOrFail();
            foreach ($plan['driver_links'] as $link) {
                $user = $usersByCode[$link['code']];
                $driver = Driver::findOrFail($link['driver_id']);
                $this->save($driver, ['user_id' => $user->id]);
                if (! $user->hasRole($driverRole)) {
                    $user->assignRole($driverRole);
                    app(AuditLogger::class)->log(null, 'hr.driver_role_granted', $user, metadata: ['driver_id' => $driver->id]);
                }
            }
        }
        $headRole = $grantHeadRole ? Role::where('name', 'department_head')->where('guard_name', 'web')->firstOrFail() : null;
        foreach (['departments' => Department::class, 'divisions' => HrDivision::class] as $key => $class) {
            foreach ($data[$key] as $code => $row) {
                $manager = $usersByEmail[$row['manager_email']] ?? null;
                $this->save($class::findOrFail($ids[$key][$code]), ['manager_email' => $row['manager_email'] ?: null, 'manager_user_id' => $manager?->id]);
                if ($headRole && $manager && ! $manager->hasRole($headRole)) {
                    $manager->assignRole($headRole);
                    app(AuditLogger::class)->log(null, 'hr.manager_role_granted', $manager, metadata: ['role' => 'department_head']);
                }
            }
        }
        app(AuditLogger::class)->log(null, 'hr.master_data_imported', metadata: ['counts' => $plan['counts'], 'user_counts' => $plan['user_counts'], 'grant_head_role' => $grantHeadRole]);
    }

    private function save(Model $model, array $values): void
    {
        $before = $model->exists ? array_diff_key($model->getAttributes(), array_flip(['password', 'remember_token'])) : null;
        $model->fill($values);
        if (! $model->exists || $model->isDirty()) {
            $model->save();
            $after = array_diff_key($model->getAttributes(), array_flip(['password', 'remember_token']));
            app(AuditLogger::class)->log(null, 'hr.master_data_updated', $model, $before, $after);
        }
    }

    private function issue(array &$plan, string $severity, string $sheet, array $row, string $message): void
    {
        $plan[$severity][] = ['sheet' => self::SHEETS[$sheet][0], 'row' => $row['row'], 'code' => $row['code'], 'message' => $message];
    }

    private function text(mixed $value): string
    {
        return trim(str_replace("\u{00A0}", ' ', (string) $value));
    }
}
