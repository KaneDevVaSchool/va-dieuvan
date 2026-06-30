<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LegacyImport\LegacyDispatchRequestImporter;
use App\Services\LegacyImport\LegacyTripCostImporter;
use App\Services\LegacyImport\LegacyTripRecordImporter;
use App\Services\LegacyImport\LegacyVehicleImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

    private const VALID_SHEETS = ['vehicles', 'passenger', 'cargo', 'costs', 'odometer'];

    public function handle(
        LegacyVehicleImporter $vehicleImporter,
        LegacyDispatchRequestImporter $dispatchImporter,
        LegacyTripCostImporter $costImporter,
        LegacyTripRecordImporter $recordImporter,
    ): int {
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

        // Step 1: vehicles (must run first to build the lookup map)
        $vehicleMap = [];
        if (in_array('vehicles', $sheets, true)) {
            $this->info('▶ [1/5] Import xe (Sheet 3)…');
            $vehicleMap = $vehicleImporter->import($filePath, $dryRun);
            $this->line('   Xe đã upsert: <comment>'.count($vehicleMap).'</comment>');
            $this->newLine();
        } else {
            // Even if vehicles sheet is skipped, load existing plate→id map from DB
            $vehicleMap = $this->loadExistingVehicleMap();
            $this->line('[vehicles skipped] Đã load '.count($vehicleMap).' xe từ DB.');
            $this->newLine();
        }

        // Resolve the system user ID needed for requester_id / created_by
        // In dry-run there is no DB available, so use a placeholder.
        $systemUserId = $dryRun ? 0 : $this->ensureSystemUser();

        // Steps 2 & 3: passenger and/or cargo dispatch requests
        // Both are processed in a single file pass to avoid opening the XLSX twice.
        $doPassenger = in_array('passenger', $sheets, true);
        $doCargo = in_array('cargo', $sheets, true);

        if ($doPassenger || $doCargo) {
            $label = match (true) {
                $doPassenger && $doCargo => '[2-3/5] Import phiếu hành khách + hàng hóa (Sheets 1 & 2)…',
                $doPassenger => '[2/5] Import phiếu hành khách / công tác (Sheet 1)…',
                default => '[3/5] Import phiếu hàng hóa (Sheet 2)…',
            };

            $this->info("▶ {$label}");
            $drStats = $dispatchImporter->import($filePath, $vehicleMap, $dryRun, $doPassenger, $doCargo);

            if ($doPassenger) {
                $this->printDispatchStats('Hành khách', $drStats['passenger']);
            }
            if ($doCargo) {
                $this->printDispatchStats('Hàng hóa', $drStats['cargo']);
            }
            $this->newLine();
        }

        // Step 4: vehicle-level standalone costs
        if (in_array('costs', $sheets, true)) {
            $this->info('▶ [4/5] Import chi phí tổng xe (Sheet 4)…');
            $costStats = $costImporter->import($filePath, $systemUserId, $dryRun);
            $this->printSimpleStats('Chi phí tổng xe', $costStats, 'created');
            $this->newLine();
        }

        // Step 5: per-vehicle odometer log → TripCost rows
        if (in_array('odometer', $sheets, true)) {
            $this->info('▶ [5/5] Import nhật ký xe 51A-796.68 (Sheet 5)…');
            $odoStats = $recordImporter->import($filePath, $systemUserId, $vehicleMap, $dryRun);
            $this->printSimpleStats('Odometer / chi phí xe', $odoStats, 'created_costs');
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

    /**
     * Load all existing vehicles from the DB into a plate→id map so that
     * rows in sheets 1 & 2 can still resolve vehicle_id even when the
     * --sheet=vehicles step is skipped.
     *
     * @return array<string,int>
     */
    private function loadExistingVehicleMap(): array
    {
        return \App\Models\Vehicle::query()
            ->select(['id', 'license_plate'])
            ->get()
            ->mapWithKeys(fn ($v) => [mb_strtoupper($v->license_plate) => $v->id])
            ->all();
    }

    private function ensureSystemUser(): int
    {
        $user = User::firstOrCreate(
            ['email' => 'legacy-import@va.edu.vn'],
            [
                'name' => 'Legacy Import System',
                'password' => bcrypt(Str::random(32)),
                'is_active' => false,
            ],
        );

        return $user->id;
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
