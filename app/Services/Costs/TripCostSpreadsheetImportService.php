<?php

namespace App\Services\Costs;

use App\Models\TripCost;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TripCostSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'ngày ghi nhận' => 'reported_on',
        'ngay ghi nhan' => 'reported_on',
        'ngày' => 'reported_on',
        'ngay' => 'reported_on',
        'loại chi phí' => 'type',
        'loai chi phi' => 'type',
        'loại' => 'type',
        'loai' => 'type',
        'số tiền' => 'amount',
        'so tien' => 'amount',
        'tiền' => 'amount',
        'tien' => 'amount',
        'mô tả' => 'description',
        'mo ta' => 'description',
        'id chuyến (tùy chọn)' => 'trip_id',
        'id chuyen (tuy chon)' => 'trip_id',
        'id chuyến' => 'trip_id',
        'id chuyen' => 'trip_id',
        'trip_id' => 'trip_id',
    ];

    /** @var list<string> */
    private const VALID_TYPES = ['fuel', 'toll', 'parking', 'maintenance', 'driver_fee', 'other'];

    /**
     * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromPath(string $path, ?int $actorId): array
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
        if (! isset($colMap['reported_on'], $colMap['type'], $colMap['amount'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Ngày ghi nhận, Loại chi phí, Số tiền).']],
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
                $payload = $this->normalizeRow($rowData, $actorId);
                TripCost::create($payload);
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
            if (isset($map['reported_on']) || isset($map['amount'])) {
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
    private function normalizeRow(array $row, ?int $actorId): array
    {
        $reportedOnRaw = trim((string) ($row['reported_on'] ?? ''));
        if ($reportedOnRaw === '') {
            throw new \InvalidArgumentException('Thiếu ngày ghi nhận.');
        }
        $reportedOn = $this->parseDate($reportedOnRaw);

        $type = strtolower(trim((string) ($row['type'] ?? '')));
        if (! in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Loại chi phí không hợp lệ: '.$type.'. Các giá trị hợp lệ: '.implode(', ', self::VALID_TYPES));
        }

        $amountRaw = trim((string) ($row['amount'] ?? ''));
        if ($amountRaw === '' || ! is_numeric(str_replace([',', ' '], '', $amountRaw))) {
            throw new \InvalidArgumentException('Số tiền không hợp lệ.');
        }
        $amount = (float) str_replace([',', ' '], '', $amountRaw);

        $payload = [
            'reported_on' => $reportedOn->toDateString(),
            'type' => $type,
            'amount' => $amount,
            'currency' => 'VND',
            'status' => 'pending',
            'created_by' => $actorId,
        ];

        $description = trim((string) ($row['description'] ?? ''));
        if ($description !== '') {
            $payload['description'] = $description;
        }

        $tripIdRaw = trim((string) ($row['trip_id'] ?? ''));
        if ($tripIdRaw !== '' && is_numeric($tripIdRaw)) {
            $payload['trip_id'] = (int) $tripIdRaw;
        }

        return $payload;
    }

    private function parseDate(string $value): Carbon
    {
        $v = trim($value);
        try {
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $v)) {
                return Carbon::createFromFormat('d/m/Y', $v)->startOfDay();
            }

            return Carbon::parse($v)->startOfDay();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Ngày không hợp lệ: '.$v);
        }
    }
}
