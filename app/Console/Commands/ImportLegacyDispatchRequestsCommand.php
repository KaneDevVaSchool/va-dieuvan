<?php

namespace App\Console\Commands;

use App\Services\LegacyImport\LegacyDispatchImportOrchestrator;
use Illuminate\Console\Command;

/**
 * One-time Artisan command to import legacy "Phiếu đề xuất ghi nhận" Excel data.
 *
 * Usage examples:
 *   php artisan dispatch-request:import-legacy
 *   php artisan dispatch-request:import-legacy --dry-run
 *   php artisan dispatch-request:import-legacy --file=database/custom.xlsx
 *   php artisan dispatch-request:import-legacy --sheet=vehicles
 *   php artisan dispatch-request:import-legacy --sheet=passenger --sheet=cargo
 *
 * Execution order (when running all sheets):
 *   1. vehicles  (Sheet 3) — build plate→id lookup map first
 *   2. passenger (Sheet 1) — dispatch_requests + trips (business / point_to_point)
 *   3. cargo     (Sheet 2) — dispatch_requests + trips (cargo)
 *   4. costs     (Sheet 4) — standalone trip_costs (vehicle-level expenses)
 *   5. odometer  (Sheet 5) — trip_costs for fuel/maintenance of 51A-796.68
 */
class ImportLegacyDispatchRequestsCommand extends Command
{
    protected $signature = 'dispatch-request:import-legacy
        {--file=           : Path to the Excel file (defaults to first *.xlsx in database/)}
        {--sheet=*         : Which sheet(s) to import: all|vehicles|passenger|cargo|costs|odometer (default: all)}
        {--dry-run         : Parse and report counts without writing to the database}';

    protected $description = 'Import legacy dispatch-request history from the "Phiếu đề xuất ghi nhận" Excel file';

    private const VALID_SHEETS = LegacyDispatchImportOrchestrator::VALID_SHEETS;

    public function handle(LegacyDispatchImportOrchestrator $orchestrator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $filePath = $this->resolveFilePath();
        if ($filePath === null) {
            $this->error('Không tìm thấy file Excel. Dùng --file= để chỉ định đường dẫn.');

            return self::FAILURE;
        }

        $this->line("File: <comment>{$filePath}</comment>");

        if ($dryRun) {
            $this->warn('[DRY-RUN] Chỉ đếm — không ghi vào cơ sở dữ liệu.');
        }

        $sheets = $this->resolveSheets();
        $this->line('Sheets cần import: <comment>'.implode(', ', $sheets).'</comment>');
        $this->newLine();

        $result = $orchestrator->run($filePath, $sheets, $dryRun);

        if (isset($result['vehicles']['upserted'])) {
            $this->info('▶ Import xe (Sheet 3)…');
            $this->line('   Xe đã upsert: <comment>'.$result['vehicles']['upserted'].'</comment>');
            $this->newLine();
        } elseif (! empty($result['vehicles']['skipped'])) {
            $this->line('[vehicles skipped] Đã load '.($result['vehicles']['loaded_from_db'] ?? 0).' xe từ DB.');
            $this->newLine();
        }

        if ($result['passenger'] !== null) {
            $this->info('▶ Import phiếu hành khách / công tác (Sheet 1)…');
            $this->printDispatchStats('Hành khách', $result['passenger']);
            $this->newLine();
        }
        if ($result['cargo'] !== null) {
            $this->info('▶ Import phiếu hàng hóa (Sheet 2)…');
            $this->printDispatchStats('Hàng hóa', $result['cargo']);
            $this->newLine();
        }

        if ($result['costs'] !== null) {
            $this->info('▶ Import chi phí tổng xe (Sheet 4)…');
            $this->printSimpleStats('Chi phí tổng xe', $result['costs'], 'created');
            $this->newLine();
        }

        if ($result['odometer'] !== null) {
            $this->info('▶ Import nhật ký xe (Sheet 5)…');
            $this->printSimpleStats('Odometer / chi phí xe', $result['odometer'], 'created_costs');
            $this->newLine();
        }

        $this->info('✔ Hoàn tất.'.($dryRun ? ' (dry-run — không có gì được ghi)' : ''));

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function resolveFilePath(): ?string
    {
        $opt = $this->option('file');
        if ($opt) {
            return file_exists($opt) ? $opt : null;
        }

        $found = glob(base_path('database/*.xlsx'));

        return $found ? $found[0] : null;
    }

    /** @return string[] */
    private function resolveSheets(): array
    {
        $raw = (array) $this->option('sheet');

        if (empty($raw) || in_array('all', $raw, true)) {
            return self::VALID_SHEETS;
        }

        $invalid = array_diff($raw, self::VALID_SHEETS);
        if ($invalid) {
            $this->warn('Sheet không hợp lệ bị bỏ qua: '.implode(', ', $invalid));
        }

        $valid = array_intersect($raw, self::VALID_SHEETS);

        return array_values($valid);
    }

    /** @param  array<string,int>  $stats */
    private function printDispatchStats(string $label, array $stats): void
    {
        $this->line("   [{$label}] Đọc: <comment>{$stats['parsed']}</comment> | Hợp lệ: <comment>{$stats['valid']}</comment> | Bỏ qua: <comment>{$stats['skipped']}</comment> | Lỗi: <comment>{$stats['errors']}</comment>");
        $this->line("   [{$label}] Tạo phiếu: <comment>{$stats['created_requests']}</comment> | Tạo chuyến: <comment>{$stats['created_trips']}</comment> | Trùng: <comment>{$stats['duplicates']}</comment>");
    }

    /** @param  array<string,int>  $stats */
    private function printSimpleStats(string $label, array $stats, string $createdKey): void
    {
        $this->line("   [{$label}] Đọc: <comment>{$stats['parsed']}</comment> | Bỏ qua: <comment>{$stats['skipped']}</comment> | Lỗi: <comment>{$stats['errors']}</comment> | Đã tạo: <comment>{$stats[$createdKey]}</comment>");
    }
}
