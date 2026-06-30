<?php

namespace App\Services\LegacyImport;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Reads Sheets 1 & 2 from the legacy Excel file and imports them
 * into dispatch_requests + trips tables.
 *
 * Sheet 1 "DX Hanh khach Cong tac" — index 0, header row 3, data from row 4.
 * Sheet 2 "DX Hang hoa"            — index 1, header row 2, data from row 3.
 */
class LegacyDispatchRequestImporter
{
    private const CHUNK_SIZE = 100;

    private const PASSENGER_SHEET_INDEX = 0;

    private const PASSENGER_HEADER_ROWS = 3;  // data starts on row 4

    private const CARGO_SHEET_INDEX = 1;

    private const CARGO_HEADER_ROWS = 2;  // data starts on row 3

    public function __construct(
        private readonly PassengerRowMapper $passengerMapper,
        private readonly CargoRowMapper $cargoMapper,
    ) {}

    /**
     * Import passenger and/or cargo sheets.
     *
     * @param  array<string,int>  $vehicleMap  normalised plate → vehicle_id
     * @return array{
     *   passenger: array{parsed:int,valid:int,skipped:int,errors:int,created_requests:int,created_trips:int,duplicates:int},
     *   cargo:     array{parsed:int,valid:int,skipped:int,errors:int,created_requests:int,created_trips:int,duplicates:int},
     * }
     */
    public function import(
        string $filePath,
        array $vehicleMap,
        bool $dryRun = false,
        bool $includePassenger = true,
        bool $includeCargo = true,
    ): array {
        $systemUserId = $dryRun ? 0 : $this->ensureSystemUser();

        $stats = [
            'passenger' => $this->blankStats(),
            'cargo' => $this->blankStats(),
        ];

        $reader = new XlsxReader;
        $reader->open($filePath);

        $sheetIdx = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheetIdx === self::PASSENGER_SHEET_INDEX) {
                if ($includePassenger) {
                    $this->processSheet(
                        $sheet,
                        self::PASSENGER_HEADER_ROWS,
                        $this->passengerMapper,
                        $systemUserId,
                        $vehicleMap,
                        $stats['passenger'],
                        $dryRun,
                    );
                }
            } elseif ($sheetIdx === self::CARGO_SHEET_INDEX) {
                if ($includeCargo) {
                    $this->processSheet(
                        $sheet,
                        self::CARGO_HEADER_ROWS,
                        $this->cargoMapper,
                        $systemUserId,
                        $vehicleMap,
                        $stats['cargo'],
                        $dryRun,
                    );
                }
                break; // Sheets 3-5 are handled by other importers; stop early.
            } else {
                break;
            }

            $sheetIdx++;
        }

        $reader->close();

        return $stats;
    }

    /** @param  array<string,mixed>  $stats */
    private function processSheet(
        \OpenSpout\Reader\SheetInterface $sheet,
        int $headerRows,
        PassengerRowMapper|CargoRowMapper $mapper,
        int $systemUserId,
        array $vehicleMap,
        array &$stats,
        bool $dryRun,
    ): void {
        $rowNum = 0;
        $buffer = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rowNum++;

            if ($rowNum <= $headerRows) {
                continue;
            }

            $stats['parsed']++;
            $cells = $row->getCells();

            try {
                $mapped = $mapper->map($cells, $rowNum, $systemUserId, $vehicleMap);
            } catch (\Throwable $e) {
                $stats['errors']++;

                continue;
            }

            if ($mapped === null) {
                $stats['skipped']++;

                continue;
            }

            $stats['valid']++;
            $buffer[] = $mapped;

            if (count($buffer) >= self::CHUNK_SIZE) {
                $this->flush($buffer, $stats, $dryRun);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $this->flush($buffer, $stats, $dryRun);
        }
    }

    /** @param  array<int,array{request:array,trip:?array}>  $buffer */
    private function flush(array $buffer, array &$stats, bool $dryRun): void
    {
        if ($dryRun) {
            $stats['created_requests'] += count($buffer);
            $stats['created_trips'] += count(array_filter($buffer, fn ($r) => $r['trip'] !== null));

            return;
        }

        DB::transaction(function () use ($buffer, &$stats): void {
            foreach ($buffer as $mapped) {
                $importKey = $mapped['request']['wizard_snapshot']['_import_key'] ?? null;

                // Idempotent re-run guard
                if ($importKey !== null && $this->importKeyExists($importKey)) {
                    $stats['duplicates']++;

                    continue;
                }

                $dr = DispatchRequest::create($mapped['request']);
                $stats['created_requests']++;

                if ($mapped['trip'] !== null) {
                    Trip::create(array_merge($mapped['trip'], ['dispatch_request_id' => $dr->id]));
                    $stats['created_trips']++;
                }
            }
        });
    }

    private function importKeyExists(string $key): bool
    {
        return DispatchRequest::where('source_channel', 'paper')
            ->whereNotNull('wizard_snapshot')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(wizard_snapshot, '$._import_key')) = ?", [$key])
            ->exists();
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

    /** @return array{parsed:int,valid:int,skipped:int,errors:int,created_requests:int,created_trips:int,duplicates:int} */
    private function blankStats(): array
    {
        return [
            'parsed' => 0,
            'valid' => 0,
            'skipped' => 0,
            'errors' => 0,
            'created_requests' => 0,
            'created_trips' => 0,
            'duplicates' => 0,
        ];
    }
}
