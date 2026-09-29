<?php

namespace App\Console\Commands;

use App\Services\Maintenance\TestDataPurgeService;
use Illuminate\Console\Command;

/**
 * Xóa sạch dữ liệu kiểm thử trước go-live: nghiệp vụ, xe/tài xế, học sinh TP, audit/thông báo,
 * bảng giá; gỡ role driver khỏi mọi user (tài khoản giữ nguyên — bước import gán lại cho tài xế thật).
 * Giữ users, roles/permissions và cấu hình hệ thống.
 * Mặc định chỉ dry-run; cần --force mới xóa thật.
 */
class PurgeTestDataCommand extends Command
{
    protected $signature = 'data:purge-test
                            {--force : Thực sự xóa (mặc định chỉ in kế hoạch — dry-run)}
                            {--keep-files : Không xóa file đính kèm / file import trên disk}';

    protected $description = 'Xóa sạch dữ liệu test (nghiệp vụ, xe/tài xế, học sinh TP, audit, bảng giá) và gỡ role driver';

    public function handle(TestDataPurgeService $service): int
    {
        $groups = array_keys(TestDataPurgeService::GROUPS);

        $this->info('Database: '.config('database.connections.'.config('database.default').'.database'));

        $plan = $service->plan($groups);
        $this->table(['Nhóm', 'Bảng', 'Số dòng sẽ xóa'], array_map(
            fn (array $r) => [$r['group'], $r['table'], $r['rows']],
            $plan,
        ));

        $users = $service->driverRoleUsers();
        $this->line('');
        $this->info('User sẽ bị gỡ role driver (giữ tài khoản): '.count($users));
        if ($users !== []) {
            $this->table(['ID', 'Tên', 'Email', 'Mã NV', 'Role còn lại'], array_map(
                fn (array $u) => [$u['id'], $u['name'], $u['email'], $u['employee_code'] ?? '', $u['other_roles'] ?: '(không)'],
                $users,
            ));
        }

        $unclassified = $service->unclassifiedTables();
        if ($unclassified !== []) {
            $this->warn('Bảng chưa phân loại (KHÔNG bị xóa): '.implode(', ', $unclassified));
        }

        if (! $this->option('force')) {
            $this->line('');
            $this->comment('Dry-run: chưa xóa gì. Chạy lại với --force để xóa thật (nhớ backup DB trước).');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive()
            && ! $this->confirm('Xác nhận XÓA VĨNH VIỄN dữ liệu ở trên? Đã backup DB chưa?', false)) {
            $this->warn('Đã huỷ.');

            return self::FAILURE;
        }

        $result = $service->purge($groups, true, ! $this->option('keep-files'));

        $this->info(sprintf(
            'Xong. Đã xóa %d dòng trên %d bảng, gỡ role driver của %d user; file: %d đã xóa, %d không tìm thấy.',
            array_sum($result['tables']),
            count($result['tables']),
            $result['users'],
            $result['files_deleted'],
            $result['files_missing'],
        ));

        return self::SUCCESS;
    }
}
