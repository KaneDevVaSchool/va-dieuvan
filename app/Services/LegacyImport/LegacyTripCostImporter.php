<?php

namespace App\Services\LegacyImport;

use App\Models\TripCost;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Imports Sheet 4 "Chi phí tổng xe" into trip_costs (trip_id = null, vehicle standalone costs).
 *
 * Sheet 4 column layout (0-indexed):
 *   Merged header rows 4–5; data starts at row 6.
 *   A(0)  — empty
 *   B(1)  — STT
 *   C(2)  — ĐƠN VỊ (unit / cost category group)
 *   D(3)  — PHÂN LOẠI (cost type)
 *   E(4)  — NGƯỜI ĐỀ XUẤT (requester name)
 *   F(5)  — NỘI DUNG (description)
 *   G(6)  — NHÀ CUNG CẤP (vendor)
 *   H(7)  — CHI PHÍ Tạm ứng (advance — ignored)
 *   I(8)  — CHI PHÍ Thanh toán (actual amount)
 *   J(9)  — THỜI GIAN (reported_on date)
 *   K(10) — NGƯỜI PHỤ TRÁCH (handler)
 *   L(11) — CHỨNG TỪ (receipt ref)
 *   M(12) — PHÁP NHÂN TT (entity)
 *   N(13) — GHI CHÚ (notes)
 */
class LegacyTripCostImporter
{
    use CellValueParser;

    private const SHEET_INDEX = 3;  // 0-based (Sheet 4)

    private const DATA_START_ROW = 6;

    private const CHUNK_SIZE = 100;

    private const C_STT = 1;

    private const C_UNIT = 2;

    private const C_TYPE = 3;

    private const C_REQUESTER = 4;

    private const C_DESC = 5;

    private const C_VENDOR = 6;

    private const C_AMOUNT = 8;  // "Thanh toán" column

    private const C_DATE = 9;

    private const C_HANDLER = 10;

    private const C_RECEIPT = 11;

    private const C_NOTES = 13;

    /**
     * @return array{parsed:int,skipped:int,errors:int,created:int,duplicates:int}
     */
    public function import(string $filePath, int $systemUserId, bool $dryRun = false): array
    {
        $stats = ['parsed' => 0, 'skipped' => 0, 'errors' => 0, 'created' => 0, 'duplicates' => 0];

        $reader = new XlsxReader;
        $reader->open($filePath);

        $sheetIdx = 0;
        $buffer = [];

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

                if ($this->isRowEmpty($cells, [self::C_STT, self::C_TYPE, self::C_AMOUNT])) {
                    $stats['skipped']++;

                    continue;
                }

                $amount = $this->cellFloat($cells, self::C_AMOUNT);

                if ($amount === null || $amount <= 0) {
                    $stats['skipped']++;

                    continue;
                }

                $stats['parsed']++;

                $reportedOn = $this->cellDate($cells, self::C_DATE)?->format('Y-m-d');
                $legacyRef = '[legacy:cost:sheet4:row:'.$rowNum.']';

                // Build a description combining available text fields
                $descParts = array_filter([
                    $this->cellStr($cells, self::C_DESC),
                    $this->cellStr($cells, self::C_VENDOR) !== null
                        ? 'NCC: '.$this->cellStr($cells, self::C_VENDOR)
                        : null,
                    $this->cellStr($cells, self::C_HANDLER) !== null
                        ? 'Phụ trách: '.$this->cellStr($cells, self::C_HANDLER)
                        : null,
                    $this->cellStr($cells, self::C_NOTES),
                ]);

                $buffer[] = [
                    'trip_id' => null,
                    'created_by' => $systemUserId,
                    'type' => $this->mapCostType($this->cellStr($cells, self::C_TYPE)),
                    'amount' => $amount,
                    'currency' => 'VND',
                    'description' => mb_substr(trim($legacyRef.' '.($descParts ? implode(' | ', $descParts) : '')), 0, 255),
                    'reported_on' => $reportedOn,
                    'status' => 'confirmed',
                    '_legacy_ref' => $legacyRef,
                ];

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

        return $stats;
    }

    /** @param  array<int,array<string,mixed>>  $buffer */
    private function flush(array $buffer, array &$stats, bool $dryRun): void
    {
        if ($dryRun) {
            $stats['created'] += count($buffer);

            return;
        }

        DB::transaction(function () use ($buffer, &$stats): void {
            foreach ($buffer as $row) {
                $ref = $row['_legacy_ref'] ?? null;
                unset($row['_legacy_ref']);
                if ($ref !== null && TripCost::query()->where('description', 'like', $ref.'%')->exists()) {
                    $stats['duplicates']++;

                    continue;
                }
                TripCost::create($row);
                $stats['created']++;
            }
        });
    }

    /**
     * Maps Vietnamese cost category names to concise type strings.
     * The type column is a free-form string(64) — we normalise to consistent values.
     */
    private function mapCostType(?string $raw): string
    {
        if ($raw === null) {
            return 'other';
        }

        $lower = mb_strtolower(trim($raw));

        return match (true) {
            str_contains($lower, 'xăng') || str_contains($lower, 'dầu') => 'fuel',
            str_contains($lower, 'btbd') || str_contains($lower, 'bảo dưỡng') => 'maintenance',
            str_contains($lower, 'cầu đường') || str_contains($lower, 'toll') => 'toll',
            str_contains($lower, 'bãi xe') || str_contains($lower, 'đỗ xe') => 'parking',
            str_contains($lower, 'bảo hiểm') => 'insurance',
            default => $raw,
        };
    }
}
