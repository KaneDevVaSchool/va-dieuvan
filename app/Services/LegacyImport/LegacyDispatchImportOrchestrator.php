<?php

namespace App\Services\LegacyImport;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Str;

/**
 * Chạy pipeline import Excel «Phiếu đề xuất ghi nhận» (dùng chung Artisan + API).
 */
class LegacyDispatchImportOrchestrator
{
    public const VALID_SHEETS = ['vehicles', 'passenger', 'cargo', 'costs', 'odometer'];

    public function __construct(
        private readonly LegacyVehicleImporter $vehicleImporter,
        private readonly LegacyDispatchRequestImporter $dispatchImporter,
        private readonly LegacyTripCostImporter $costImporter,
        private readonly LegacyTripRecordImporter $recordImporter,
    ) {}

    /**
     * @param  string[]  $sheets  Subset of VALID_SHEETS; empty = all sheets.
     * @return array<string, mixed>
     */
    public function run(string $filePath, array $sheets = [], bool $dryRun = false, ?LegacyImportDiagnostics $diagnostics = null): array
    {
        $sheets = $this->normalizeSheets($sheets);

        $vehicleMap = [];
        $result = [
            'dry_run' => $dryRun,
            'sheets' => $sheets,
            'vehicles' => null,
            'passenger' => null,
            'cargo' => null,
            'costs' => null,
            'odometer' => null,
            'issues' => [],
            'issue_count' => 0,
        ];

        if (in_array('vehicles', $sheets, true)) {
            $vehicleMap = $this->vehicleImporter->import($filePath, $dryRun);
            $result['vehicles'] = ['upserted' => count($vehicleMap)];
        } else {
            $vehicleMap = $this->loadExistingVehicleMap();
            $result['vehicles'] = ['skipped' => true, 'loaded_from_db' => count($vehicleMap)];
        }

        $systemUserId = $dryRun ? 0 : $this->ensureSystemUser();

        $doPassenger = in_array('passenger', $sheets, true);
        $doCargo = in_array('cargo', $sheets, true);

        if ($doPassenger || $doCargo) {
            $drStats = $this->dispatchImporter->import(
                $filePath,
                $vehicleMap,
                $dryRun,
                $doPassenger,
                $doCargo,
                $diagnostics,
            );
            if ($doPassenger) {
                $result['passenger'] = $drStats['passenger'];
            }
            if ($doCargo) {
                $result['cargo'] = $drStats['cargo'];
            }
        }

        if (in_array('costs', $sheets, true)) {
            $result['costs'] = $this->costImporter->import($filePath, $systemUserId, $dryRun);
        }

        if (in_array('odometer', $sheets, true)) {
            $result['odometer'] = $this->recordImporter->import($filePath, $systemUserId, $vehicleMap, $dryRun);
        }

        if ($diagnostics !== null) {
            $result['issues'] = $diagnostics->take(500);
            $result['issue_count'] = $diagnostics->count();
        }

        return $result;
    }

    /** @return string[] */
    public function normalizeSheets(array $sheets): array
    {
        $raw = array_values(array_filter(array_map('strval', $sheets)));

        if ($raw === [] || in_array('all', $raw, true)) {
            return self::VALID_SHEETS;
        }

        return array_values(array_intersect($raw, self::VALID_SHEETS));
    }

    /**
     * @return array<string, int>
     */
    private function loadExistingVehicleMap(): array
    {
        $map = [];
        foreach (Vehicle::query()->select(['id', 'license_plate'])->get() as $vehicle) {
            $display = mb_strtoupper(trim((string) $vehicle->license_plate));
            $key = (string) preg_replace('/[^A-Z0-9.]/u', '', $display);
            $map[$display] = (int) $vehicle->id;
            if ($key !== '') {
                $map[$key] = (int) $vehicle->id;
            }
        }

        return $map;
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

        return (int) $user->id;
    }
}
