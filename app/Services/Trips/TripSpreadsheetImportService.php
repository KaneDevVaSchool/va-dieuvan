<?php

namespace App\Services\Trips;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TripSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'ngày giờ đi' => 'depart_at',
        'ngay gio di' => 'depart_at',
        'ngày giờ về' => 'arrive_by',
        'ngay gio ve' => 'arrive_by',
        'trạng thái' => 'status',
        'trang thai' => 'status',
        'biển số xe' => 'vehicle_license_plate',
        'bien so xe' => 'vehicle_license_plate',
        'biển số' => 'vehicle_license_plate',
        'bien so' => 'vehicle_license_plate',
        'tên tài xế' => 'driver_name',
        'ten tai xe' => 'driver_name',
        'tài xế' => 'driver_name',
        'tai xe' => 'driver_name',
    ];

    /** @var array<string, string> */
    private const STATUS_MAP = [
        'pending' => 'pending',
        'chờ' => 'pending',
        'cho' => 'pending',
        'assigned' => 'assigned',
        'đã phân công' => 'assigned',
        'da phan cong' => 'assigned',
        'in_progress' => 'in_progress',
        'đang đi' => 'in_progress',
        'dang di' => 'in_progress',
        'completed' => 'completed',
        'hoàn thành' => 'completed',
        'hoan thanh' => 'completed',
        'done' => 'completed',
        'cancelled' => 'cancelled',
        'hủy' => 'cancelled',
        'huy' => 'cancelled',
    ];

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
        if (! isset($colMap['depart_at'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Ngày giờ đi).']],
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
                Trip::create($payload);
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
            if (isset($map['depart_at'])) {
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
        $departRaw = trim((string) ($row['depart_at'] ?? ''));
        $departAt = $this->parseDateTime($departRaw);

        $statusRaw = mb_strtolower(trim((string) ($row['status'] ?? '')));
        $status = $statusRaw !== '' ? (self::STATUS_MAP[$statusRaw] ?? 'pending') : 'pending';

        $payload = [
            'depart_at' => $departAt,
            'status' => $status,
            'dispatcher_id' => $actorId,
        ];

        $arriveRaw = trim((string) ($row['arrive_by'] ?? ''));
        if ($arriveRaw !== '') {
            $payload['arrive_by'] = $this->parseDateTime($arriveRaw);
        }

        $plate = mb_strtoupper(trim((string) ($row['vehicle_license_plate'] ?? '')));
        if ($plate !== '') {
            $vehicle = Vehicle::where('license_plate', $plate)->first();
            if ($vehicle) {
                $payload['vehicle_id'] = $vehicle->id;
            }
        }

        $driverName = trim((string) ($row['driver_name'] ?? ''));
        if ($driverName !== '') {
            $driver = Driver::where('full_name', $driverName)->first();
            if ($driver) {
                $payload['driver_id'] = $driver->id;
            }
        }

        return $payload;
    }

    private function parseDateTime(string $value): Carbon
    {
        $v = trim($value);
        if ($v === '') {
            throw new \InvalidArgumentException('Thiếu ngày giờ đi.');
        }
        try {
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}\s+\d{1,2}:\d{2}$#', $v)) {
                return Carbon::createFromFormat('d/m/Y H:i', $v);
            }
            if (preg_match('#^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}$#', $v)) {
                return Carbon::createFromFormat('Y-m-d H:i', $v);
            }

            return Carbon::parse($v);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Ngày giờ không hợp lệ: '.$v);
        }
    }
}
