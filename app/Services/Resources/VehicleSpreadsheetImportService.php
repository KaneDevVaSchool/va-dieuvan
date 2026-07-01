<?php

namespace App\Services\Resources;

use App\Models\Vehicle;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'biển số xe' => 'license_plate',
        'bien so xe' => 'license_plate',
        'biển số' => 'license_plate',
        'bien so' => 'license_plate',
        'loại xe' => 'type',
        'loai xe' => 'type',
        'loại' => 'type',
        'loai' => 'type',
        'số chỗ ngồi' => 'seat_count',
        'so cho ngoi' => 'seat_count',
        'số chỗ' => 'seat_count',
        'so cho' => 'seat_count',
        'tải trọng (kg)' => 'payload_kg',
        'tai trong (kg)' => 'payload_kg',
        'tải trọng' => 'payload_kg',
        'tai trong' => 'payload_kg',
        'chủ xe' => 'owner_name',
        'chu xe' => 'owner_name',
        'năm sản xuất' => 'year_manufactured',
        'nam san xuat' => 'year_manufactured',
        'ghi chú' => 'notes',
        'ghi chu' => 'notes',
    ];

    /** @var list<string> */
    private const VALID_TYPES = ['sedan', 'suv', 'minibus', 'bus', 'truck'];

    /**
     * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromPath(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headerRow = $this->detectHeaderRow($sheet);

        if ($headerRow === null) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => 1, 'message' => 'Không tìm thấy dòng tiêu đề cột hợp lệ.']],
            ];
        }

        $colMap = $this->mapColumns($sheet, $headerRow);
        if (! isset($colMap['license_plate'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Biển số xe).']],
            ];
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $lastRow = (int) $sheet->getHighestRow();

        for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
            $rowData = $this->readRow($sheet, $r, $colMap);
            if ($this->rowIsEmpty($rowData)) {
                continue;
            }

            try {
                $payload = $this->normalizeRow($rowData);

                if (Vehicle::withTrashed()->where('license_plate', $payload['license_plate'])->exists()) {
                    $skipped++;

                    continue;
                }

                Vehicle::create($payload);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $r, 'message' => $e->getMessage()];
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    private function detectHeaderRow(Worksheet $sheet): ?int
    {
        for ($r = 1; $r <= min(10, (int) $sheet->getHighestRow()); $r++) {
            $map = $this->mapColumns($sheet, $r);
            if (isset($map['license_plate'])) {
                return $r;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn($headerRow));
        for ($c = 1; $c <= $lastCol; $c++) {
            $label = trim((string) $sheet->getCellByColumnAndRow($c, $headerRow)->getValue());
            if ($label === '') {
                continue;
            }
            $key = $this->normalizeHeader($label);
            if ($key !== null) {
                $map[$key] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            }
        }

        return $map;
    }

    private function normalizeHeader(string $label): ?string
    {
        $plain = mb_strtolower(trim(preg_replace('/\*+$/u', '', $label) ?? $label));
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        return self::HEADER_MAP[$plain] ?? null;
    }

    /**
     * @param  array<string, string>  $colMap
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $sheet, int $row, array $colMap): array
    {
        $data = [];
        foreach ($colMap as $field => $col) {
            $data[$field] = trim((string) $sheet->getCell($col.$row)->getFormattedValue());
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $plate = mb_strtoupper(trim((string) ($row['license_plate'] ?? '')));
        if ($plate === '') {
            throw new \InvalidArgumentException('Thiếu biển số xe.');
        }

        $type = strtolower(trim((string) ($row['type'] ?? 'sedan')));
        if (! in_array($type, self::VALID_TYPES, true)) {
            $type = 'sedan';
        }

        $payload = [
            'license_plate' => $plate,
            'type' => $type,
            'status' => 'active',
        ];

        $seatCount = trim((string) ($row['seat_count'] ?? ''));
        if ($seatCount !== '' && is_numeric($seatCount)) {
            $payload['seat_count'] = (int) $seatCount;
        }

        $payloadKg = trim((string) ($row['payload_kg'] ?? ''));
        if ($payloadKg !== '' && is_numeric(str_replace([',', ' '], '', $payloadKg))) {
            $payload['payload_kg'] = (float) str_replace([',', ' '], '', $payloadKg);
        }

        $ownerName = trim((string) ($row['owner_name'] ?? ''));
        if ($ownerName !== '') {
            $payload['owner_name'] = $ownerName;
        }

        $year = trim((string) ($row['year_manufactured'] ?? ''));
        if ($year !== '' && is_numeric($year)) {
            $payload['year_manufactured'] = (int) $year;
        }

        $notes = trim((string) ($row['notes'] ?? ''));
        if ($notes !== '') {
            $payload['notes'] = $notes;
        }

        return $payload;
    }
}
