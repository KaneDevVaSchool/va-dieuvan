<?php

namespace App\Services\LegacyImport;

use App\Models\TripCost;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Imports Sheet 5 "TT xe 51A-796.68 (Mẫu)" into trip_costs.
 *
 * NOTE: trip_records.trip_id is NOT nullable (FK constrained), so we cannot
 * insert TripRecord rows without a matched trip. Instead, we create standalone
 * TripCost rows (trip_id = null) for fuel/maintenance amounts, and attach
 * odometer data to the vehicle via Vehicle.odometer_km update.
 *
 * Sheet 5 column layout (0-indexed):
 *   Merged header rows 3–4; data starts at row 5.
 *   A(0)  — empty
 *   B(1)  — STT
 *   C(2)  — Ngày (date)
 *   D(3)  — Nội dung (description / activity)
 *   E(4)  — Số KM đầu (start odometer)
 *   F(5)  — Số KM cuối (end odometer)
 *   G(6)  — Số KM đã chạy (distance — formula; may be 0 or formula string)
 *   H(7)  — Số lít xăng (litres)
 *   I(8)  — Định mức (consumption ratio — formula)
 *   J(9)  — Thành tiền: Nguyên liệu (fuel cost)
 *   K(10) — Thành tiền: Bảo trì bảo dưỡng (maintenance cost)
 *   L(11) — Thành tiền: Khác (other cost)
 *   M(12) — Ghi chú
 */
class LegacyTripRecordImporter
{
    use CellValueParser;

    private const SHEET_INDEX = 4;  // 0-based (Sheet 5)

    private const DATA_START_ROW = 5;

    private const CHUNK_SIZE = 50;

    private const C_STT = 1;

    private const C_DATE = 2;

    private const C_DESC = 3;

    private const C_ODO_START = 4;

    private const C_ODO_END = 5;

    private const C_FUEL_LITERS = 7;

    private const C_COST_FUEL = 9;

    private const C_COST_MAINT = 10;

    private const C_COST_OTHER = 11;

    private const C_NOTES = 12;

    /** Vehicle plate embedded in the sheet name (normalized). */
    private const SHEET_VEHICLE_PLATE = '51A 796.68';

    /**
     * @param  array<string,int>  $vehicleMap
     * @return array{parsed:int,skipped:int,errors:int,created_costs:int}
     */
    public function import(string $filePath, int $systemUserId, array $vehicleMap, bool $dryRun = false): array
    {
        $stats = ['parsed' => 0, 'skipped' => 0, 'errors' => 0, 'created_costs' => 0];

        $vehicleId = $vehicleMap[self::SHEET_VEHICLE_PLATE] ?? null;

        $reader = new XlsxReader;
        $reader->open($filePath);

        $sheetIdx = 0;
        $buffer = [];
        $lastOdometer = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheetIdx !== self::SHEET_INDEX) {
                $sheetIdx++;

                continue;
            }

            $rowNum = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;

                if ($rowNum < self::DATA_START_ROW) {
                    continue;
                }

                $cells = $row->getCells();

                if ($this->isRowEmpty($cells, [self::C_STT, self::C_DATE, self::C_DESC])) {
                    $stats['skipped']++;

                    continue;
                }

                $stats['parsed']++;
                $date = $this->cellDate($cells, self::C_DATE)?->format('Y-m-d');
                $desc = $this->cellStr($cells, self::C_DESC);
                $notes = $this->cellStr($cells, self::C_NOTES);
                $odoEnd = $this->cellInt($cells, self::C_ODO_END);

                if ($odoEnd !== null) {
                    $lastOdometer = $odoEnd;
                }

                $combined = implode(' | ', array_filter([$desc, $notes])) ?: null;

                // Create one TripCost row per non-zero cost type in this row
                foreach ($this->extractCosts($cells, $vehicleId, $systemUserId, $date, $combined) as $costRow) {
                    $buffer[] = $costRow;
                }

                if (count($buffer) >= self::CHUNK_SIZE) {
                    $this->flush($buffer, $stats, $dryRun);
                    $buffer = [];
                }
            }

            break;
        }

        if ($buffer !== []) {
            $this->flush($buffer, $stats, $dryRun);
        }

        $reader->close();

        // Update vehicle odometer to the last seen end-KM
        if (! $dryRun && $vehicleId !== null && $lastOdometer !== null) {
            \App\Models\Vehicle::where('id', $vehicleId)
                ->where(function ($q) use ($lastOdometer) {
                    $q->whereNull('odometer_km')->orWhere('odometer_km', '<', $lastOdometer);
                })
                ->update(['odometer_km' => $lastOdometer]);
        }

        return $stats;
    }

    /**
     * Extracts individual TripCost attribute arrays for all non-zero cost columns in a row.
     *
     * @return array<int,array<string,mixed>>
     */
    private function extractCosts(
        array $cells,
        ?int $vehicleId,
        int $systemUserId,
        ?string $date,
        ?string $description,
    ): array {
        $costs = [];

        $fuelCost = $this->cellFloat($cells, self::C_COST_FUEL);
        $maintCost = $this->cellFloat($cells, self::C_COST_MAINT);
        $otherCost = $this->cellFloat($cells, self::C_COST_OTHER);

        if ($fuelCost !== null && $fuelCost > 0) {
            $liters = $this->cellFloat($cells, self::C_FUEL_LITERS);
            $costs[] = [
                'trip_id' => null,
                'vehicle_id' => $vehicleId,
                'created_by' => $systemUserId,
                'type' => 'fuel',
                'amount' => $fuelCost,
                'currency' => 'VND',
                'description' => $description.($liters !== null ? " ({$liters} lít)" : ''),
                'reported_on' => $date,
                'status' => 'confirmed',
            ];
        }

        if ($maintCost !== null && $maintCost > 0) {
            $costs[] = [
                'trip_id' => null,
                'vehicle_id' => $vehicleId,
                'created_by' => $systemUserId,
                'type' => 'maintenance',
                'amount' => $maintCost,
                'currency' => 'VND',
                'description' => $description,
                'reported_on' => $date,
                'status' => 'confirmed',
            ];
        }

        if ($otherCost !== null && $otherCost > 0) {
            $costs[] = [
                'trip_id' => null,
                'vehicle_id' => $vehicleId,
                'created_by' => $systemUserId,
                'type' => 'other',
                'amount' => $otherCost,
                'currency' => 'VND',
                'description' => $description,
                'reported_on' => $date,
                'status' => 'confirmed',
            ];
        }

        return $costs;
    }

    /** @param  array<int,array<string,mixed>>  $buffer */
    private function flush(array $buffer, array &$stats, bool $dryRun): void
    {
        if ($dryRun) {
            $stats['created_costs'] += count($buffer);

            return;
        }

        DB::transaction(function () use ($buffer, &$stats): void {
            foreach ($buffer as $row) {
                TripCost::create($row);
                $stats['created_costs']++;
            }
        });
    }
}
