<?php

namespace App\Console\Commands;

use App\Services\Hr\HrMasterDataImportService;
use Illuminate\Console\Command;

class ImportHrMasterDataCommand extends Command
{
    protected $signature = 'hr:import-master-data
        {file : File Excel master data HCNS}
        {--dry-run : Chỉ đọc và kiểm tra, không ghi database}
        {--strict : Dừng nếu có bất kỳ cảnh báo nào}
        {--skip-user-conflicts : Bỏ qua nhân viên xung đột tài khoản, vẫn chặn mọi xung đột tài xế chính}
        {--grant-head-role : Bổ sung role department_head cho quản lý, giữ các role hiện có}
        {--branch-alias=* : Mapping tên cơ sở dạng Tên trong nhân viên=Tên trong danh mục}
        {--report= : Đường dẫn xuất báo cáo JSON (không chứa thông tin CCCD/ngân hàng)}';

    protected $description = 'Nhập lại hồ sơ nhân sự, danh mục tổ chức và quan hệ quản lý từ Excel HCNS';

    public function handle(HrMasterDataImportService $service): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $path = base_path($path);
        }
        if (! is_file($path)) {
            $this->error('Không tìm thấy file Excel.');

            return self::FAILURE;
        }
        try {
            $aliases = [];
            foreach ($this->option('branch-alias') as $pair) {
                [$from, $to] = array_pad(explode('=', $pair, 2), 2, '');
                if (trim($from) === '' || trim($to) === '') {
                    throw new \RuntimeException('--branch-alias cần có dạng Tên nguồn=Tên đích');
                }
                $aliases[trim($from)] = trim($to);
            }
            $reportPath = $this->option('report');
            if ($reportPath && (! is_dir(dirname($reportPath)) || ! is_writable(dirname($reportPath)) || (file_exists($reportPath) && ! is_writable($reportPath)))) {
                throw new \RuntimeException('Không thể ghi báo cáo: '.$reportPath);
            }
            if ($reportPath && realpath($reportPath) === realpath($path)) {
                throw new \RuntimeException('Báo cáo không được ghi đè file nguồn.');
            }
            $report = $service->import($path, (bool) $this->option('dry-run'), (bool) $this->option('strict'), (bool) $this->option('grant-head-role'), $aliases, (bool) $this->option('skip-user-conflicts'));
        } catch (\Throwable $e) {
            $this->error('Không nhập dữ liệu: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->table(['Danh mục', 'Số dòng'], collect($report['counts'])->map(fn ($count, $name) => [$name, $count])->values()->all());
        $this->line('Tài khoản: '.json_encode($report['user_counts'], JSON_UNESCAPED_UNICODE));
        if ($report['driver_links']) {
            $this->table(['Mã NV', 'Tài xế', 'Driver ID', 'User cũ', 'User đích (trống: tạo mới)', 'Thao tác'], array_map('array_values', $report['driver_links']));
        }
        $this->comment($this->option('grant-head-role')
            ? 'Có bổ sung role department_head cho quản lý khi nhập thật; giữ các quyền hiện có.'
            : 'Chỉ nhập quan hệ quản lý; chưa có role department_head thì chưa được tự gán duyệt.');
        foreach (['errors' => 'Lỗi', 'warnings' => 'Cảnh báo'] as $key => $label) {
            $this->line($label.': '.count($report[$key]));
            if ($report[$key]) {
                $this->table(['Sheet', 'Dòng', 'Mã', $label], array_map('array_values', $report[$key]));
            }
        }
        if ($reportPath) {
            $publicReport = array_diff_key($report, array_flip(['data', 'users']));
            if (file_put_contents($reportPath, json_encode($publicReport, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) {
                $this->error('Không ghi được báo cáo. Trạng thái DB: '.($report['applied'] ? 'đã nhập' : 'chưa nhập'));

                return self::FAILURE;
            }
        }
        if ($report['errors'] || ($this->option('strict') && $report['warnings'])) {
            $this->error('Dừng do lỗi kiểm tra; chưa ghi dữ liệu.');

            return self::FAILURE;
        }
        $this->info($report['applied'] ? 'Đã nhập dữ liệu. Các tài khoản và phiếu cũ được giữ nguyên ID.' : 'Dry-run hoàn tất; chưa ghi database.');

        return self::SUCCESS;
    }
}
