<?php

namespace App\Services\LegacyImport;

use App\Models\Vehicle;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Imports Sheet 3 "Thông tin xe" into the vehicles table.
 *
 * Sheet 3 column layout (0-indexed):
 *   Merged header rows 3–4; data starts at row 5.
 *   A(0)  — empty
 *   B(1)  — STT
 *   C(2)  — Tên chủ xe / Đơn vị sở hữu
 *   D(3)  — BKS (biển kiểm soát = license plate)
 *   E(4)  — Số khung / Số máy
 *   F(5)  — Loại xe (type)
 *   G(6)  — Năm sản xuất
 *   H(7)  — Năm mua xe
 *   I(8)  — Niên hạn sử dụng
 *   J(9)  — Số chỗ / Trọng tải
 *   K(10) — Bảo hiểm (tên nhà BH)
 *   L(11) — Thời hạn bảo hiểm
 *   M(12) — Hạn đăng kiểm
 *   N(13) — Phí sử dụng đường bộ
 *   O(14) — Thời gian đăng kiểm
 *   P(15) — Lần BTBD gần nhất
 *   Q(16) — Bảo dưỡng (schedule note)
 *   R(17) — Tài xế phụ trách
 *   S(18) — Ghi chú
 */
class LegacyVehicleImporter
{
    use CellValueParser;

    private const SHEET_INDEX = 2;  // 0-based (Sheet 3)

    private const DATA_START_ROW = 5;  // header spans rows 3–4

    private const C_OWNER = 2;

    private const C_PLATE = 3;

    private const C_FRAME = 4;

    private const C_TYPE = 5;

    private const C_YEAR_MADE = 6;

    private const C_YEAR_BUY = 7;

    private const C_SEATS = 9;

    private const C_INSURANCE = 10;

    private const C_INSPECTION = 12;

    private const C_CARETAKER = 17;

    private const C_NOTES = 18;

    /**
     * Reads Sheet 3 and upserts vehicles by license_plate.
     *
     * @return array<string,int> normalised_plate → vehicle_id
     */
    public function import(string $filePath, bool $dryRun = false): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $plateMap = [];
        $sheetIdx = 0;
        $processed = 0;

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
                $rawPlate = $this->cellStr($cells, self::C_PLATE);

                if ($rawPlate === null) {
                    continue;
                }

                $plate = $this->normalizePlate($rawPlate);

                if ($plate === '') {
                    continue;
                }

                $yearMade = $this->cellInt($cells, self::C_YEAR_MADE);
                $seats = $this->cellStr($cells, self::C_SEATS);

                // Seats column may contain "8" or "8 chỗ" — extract leading integer
                $seatCount = null;
                if ($seats !== null && preg_match('/^(\d+)/', $seats, $m)) {
                    $seatCount = (int) $m[1];
                }

                $data = array_filter([
                    'owner_name' => $this->cellStr($cells, self::C_OWNER),
                    'frame_engine_number' => $this->cellStr($cells, self::C_FRAME),
                    'type' => $this->cellStr($cells, self::C_TYPE),
                    'year_manufactured' => $yearMade,
                    'seat_count' => $seatCount,
                    'insurance_provider' => $this->cellStr($cells, self::C_INSURANCE),
                    'inspection_expires_at' => $this->cellDate($cells, self::C_INSPECTION)?->format('Y-m-d'),
                    'caretaker_name' => $this->cellStr($cells, self::C_CARETAKER),
                    'notes' => $this->cellStr($cells, self::C_NOTES),
                ], fn ($v) => $v !== null);

                if ($dryRun) {
                    // In dry-run we only collect plate strings; IDs stay 0
                    $key = $this->normalizePlateKey($rawPlate);
                    $plateMap[$key] = 0;
                    $plateMap[$this->normalizePlate($rawPlate)] = 0;
                } else {
                    $vehicle = Vehicle::updateOrCreate(
                        ['license_plate' => $this->normalizePlate($rawPlate)],
                        $data,
                    );
                    $key = $this->normalizePlateKey($rawPlate);
                    $plateMap[$key] = $vehicle->id;
                    $plateMap[$vehicle->license_plate] = $vehicle->id;
                    $processed++;
                }
            }

            break; // only process this one sheet
        }

        $reader->close();

        return $plateMap;
    }
}
