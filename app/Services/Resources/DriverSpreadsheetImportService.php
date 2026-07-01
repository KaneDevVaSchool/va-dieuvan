<?php

namespace App\Services\Resources;

use App\Models\Driver;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DriverSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'họ tên' => 'full_name',
        'ho ten' => 'full_name',
        'tên' => 'full_name',
        'ten' => 'full_name',
        'số điện thoại' => 'phone',
        'so dien thoai' => 'phone',
        'điện thoại' => 'phone',
        'dien thoai' => 'phone',
        'email' => 'email',
        'cccd/cmnd' => 'national_id',
        'cccd' => 'national_id',
        'cmnd' => 'national_id',
        'national_id' => 'national_id',
        'hạng bằng lái' => 'license_class',
        'hang bang lai' => 'license_class',
        'hạng bằng' => 'license_class',
        'hang bang' => 'license_class',
        'ngày hết hạn bằng lái' => 'license_expires_at',
        'ngay het han bang lai' => 'license_expires_at',
        'hết hạn bằng' => 'license_expires_at',
        'het han bang' => 'license_expires_at',
    ];

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
        if (! isset($colMap['full_name'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Họ tên).']],
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

                $query = Driver::withTrashed()->where('full_name', $payload['full_name']);
                if (! empty($payload['phone'])) {
                    $query->where('phone', $payload['phone']);
                }
                if ($query->exists()) {
                    $skipped++;

                    continue;
                }

                Driver::create($payload);
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
            if (isset($map['full_name'])) {
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
        $fullName = trim((string) ($row['full_name'] ?? ''));
        if ($fullName === '') {
            throw new \InvalidArgumentException('Thiếu họ tên tài xế.');
        }

        $payload = [
            'full_name' => $fullName,
            'employment_status' => 'active',
            'availability_status' => 'available',
        ];

        foreach (['phone', 'email', 'national_id', 'license_class'] as $field) {
            $val = trim((string) ($row[$field] ?? ''));
            if ($val !== '') {
                $payload[$field] = $val;
            }
        }

        $expiresRaw = trim((string) ($row['license_expires_at'] ?? ''));
        if ($expiresRaw !== '') {
            try {
                if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $expiresRaw)) {
                    $payload['license_expires_at'] = Carbon::createFromFormat('d/m/Y', $expiresRaw)->toDateString();
                } else {
                    $payload['license_expires_at'] = Carbon::parse($expiresRaw)->toDateString();
                }
            } catch (\Throwable) {
                // skip invalid date
            }
        }

        return $payload;
    }
}
