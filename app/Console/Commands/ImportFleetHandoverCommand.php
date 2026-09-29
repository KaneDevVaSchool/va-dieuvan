<?php

namespace App\Console\Commands;

use App\Services\Resources\FleetHandoverImportService;
use Illuminate\Console\Command;

/**
 * Import xe + tài xế từ file bàn giao (sheet "TT xe nội bộ" và "TT Tài xế").
 */
class ImportFleetHandoverCommand extends Command
{
    protected $signature = 'fleet:import-handover
                            {file : Đường dẫn file .xlsx (vd. "data/NV Điều vận_File bàn giao.xlsx")}
                            {--dry-run : Chạy thử trong transaction rồi rollback, không ghi DB}
                            {--driver-sheet=TT Tài xế : Tên sheet tài xế}
                            {--vehicle-sheet=TT xe nội bộ : Tên sheet xe}
                            {--link=* : Gán tay tài khoản khi không tự khớp được, dạng MãNV=email (lặp lại được)}';

    protected $description = 'Import/cập nhật danh sách xe và tài xế từ file Excel bàn giao';

    public function handle(FleetHandoverImportService $service): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $path = base_path($path);
        }
        if (! is_file($path)) {
            $this->error('Không tìm thấy file: '.$this->argument('file'));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $links = [];
        foreach ((array) $this->option('link') as $pair) {
            [$code, $email] = array_pad(explode('=', (string) $pair, 2), 2, '');
            if (trim($code) === '' || ! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                $this->error("--link không hợp lệ: \"{$pair}\" (cần dạng MãNV=email)");

                return self::FAILURE;
            }
            $links[trim($code)] = trim($email);
        }

        try {
            $report = $service->import(
                $path,
                $dryRun,
                (string) $this->option('driver-sheet'),
                (string) $this->option('vehicle-sheet'),
                $links,
            );
        } catch (\Throwable $e) {
            $this->error('Import thất bại, không có thay đổi nào được ghi: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Tài xế: '.count($report['drivers']));
        $this->table(['Dòng', 'Họ tên', 'Mã NV', 'Thao tác', 'Tài khoản'], array_map(
            fn (array $r) => [$r['row'], $r['name'], $r['code'], $r['action'], $r['user']],
            $report['drivers'],
        ));

        $this->info('Xe: '.count($report['vehicles']));
        $this->table(['Dòng', 'Biển số', 'Dòng xe', 'Số chỗ', 'Thao tác', 'Tài xế phụ trách'], array_map(
            fn (array $r) => [$r['row'], $r['plate'], $r['model'], $r['seats'], $r['action'], $r['driver']],
            $report['vehicles'],
        ));

        foreach ($report['warnings'] as $w) {
            $this->warn('⚠ '.$w);
        }

        $this->comment($dryRun ? 'Dry-run: đã rollback, chưa ghi gì.' : 'Đã ghi vào DB.');

        return self::SUCCESS;
    }
}
