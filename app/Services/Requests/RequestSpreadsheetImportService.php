<?php

namespace App\Services\Requests;

use App\Models\DispatchRequest;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'loại chuyến' => 'trip_type',
        'loai chuyen' => 'trip_type',
        'nơi đi' => 'origin',
        'noi di' => 'origin',
        'nơi đến' => 'destination',
        'noi den' => 'destination',
        'ngày giờ đi' => 'depart_at',
        'ngay gio di' => 'depart_at',
        'ngày giờ về' => 'arrive_by',
        'ngay gio ve' => 'arrive_by',
        'số hành khách' => 'passenger_count',
        'so hanh khach' => 'passenger_count',
        'ghi chú' => 'notes',
        'ghi chu' => 'notes',
        'trạng thái' => 'status',
        'trang thai' => 'status',
        'giá dịch vụ' => 'service_price',
        'gia dich vu' => 'service_price',
    ];

    /** @var list<string> */
    private const VALID_TRIP_TYPES = ['passenger', 'cargo', 'business'];

    /** @var array<string, string> */
    private const STATUS_MAP = [
        'pending' => 'pending',
        'chờ' => 'pending',
        'cho' => 'pending',
        'approved' => 'approved',
        'duyệt' => 'approved',
        'duyet' => 'approved',
        'done' => 'done',
        'xong' => 'done',
        'hoàn thành' => 'done',
        'hoan thanh' => 'done',
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
        if (! isset($colMap['origin'], $colMap['destination'], $colMap['depart_at'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Nơi đi, Nơi đến, Ngày giờ đi).']],
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
                DispatchRequest::create($payload);
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
            if (isset($map['origin']) || isset($map['destination'])) {
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
        $origin = trim((string) ($row['origin'] ?? ''));
        $destination = trim((string) ($row['destination'] ?? ''));

        if ($origin === '') {
            throw new \InvalidArgumentException('Thiếu nơi đi.');
        }
        if ($destination === '') {
            throw new \InvalidArgumentException('Thiếu nơi đến.');
        }

        $departRaw = trim((string) ($row['depart_at'] ?? ''));
        $departAt = $this->parseDateTime($departRaw);

        $tripType = strtolower(trim((string) ($row['trip_type'] ?? 'passenger')));
        if (! in_array($tripType, self::VALID_TRIP_TYPES, true)) {
            $tripType = 'passenger';
        }

        $payload = [
            'requester_id' => $actorId,
            'trip_type' => $tripType,
            'origin' => $origin,
            'destination' => $destination,
            'depart_at' => $departAt,
            'source_channel' => 'xlsx_import',
            'status' => 'pending',
        ];

        $arriveRaw = trim((string) ($row['arrive_by'] ?? ''));
        if ($arriveRaw !== '') {
            $payload['arrive_by'] = $this->parseDateTime($arriveRaw);
        }

        $pax = trim((string) ($row['passenger_count'] ?? ''));
        if ($pax !== '' && is_numeric($pax)) {
            $payload['passenger_count'] = (int) $pax;
        }

        $notes = trim((string) ($row['notes'] ?? ''));
        if ($notes !== '') {
            $payload['notes'] = $notes;
        }

        $statusRaw = mb_strtolower(trim((string) ($row['status'] ?? '')));
        if ($statusRaw !== '') {
            $payload['status'] = self::STATUS_MAP[$statusRaw] ?? 'pending';
        }

        $price = trim((string) ($row['service_price'] ?? ''));
        if ($price !== '' && is_numeric(str_replace([',', ' '], '', $price))) {
            $payload['service_price'] = (float) str_replace([',', ' '], '', $price);
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
