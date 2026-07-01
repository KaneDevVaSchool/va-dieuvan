<?php

namespace App\Services\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Models\TpProgram;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TpProgramSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'tên chương trình' => 'name',
        'ten chuong trinh' => 'name',
        'mã ct' => 'code',
        'ma ct' => 'code',
        'điểm đi' => 'origin_name',
        'diem di' => 'origin_name',
        'điểm đến' => 'destination_name',
        'diem den' => 'destination_name',
        'giờ đi' => 'departure_time',
        'gio di' => 'departure_time',
        'giờ về' => 'return_time',
        'gio ve' => 'return_time',
        'ngày bắt đầu' => 'start_date',
        'ngay bat dau' => 'start_date',
        'ngày kết thúc' => 'end_date',
        'ngay ket thuc' => 'end_date',
        'thứ trong tuần' => 'runs_on',
        'thu trong tuan' => 'runs_on',
        'chi phí/chuyến' => 'cost_per_trip',
        'chi phi/chuyen' => 'cost_per_trip',
        'ghi chú' => 'notes',
        'ghi chu' => 'notes',
    ];

    public function __construct(
        private readonly CreateTransportProgramAction $createAction,
    ) {}

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
        if (! isset($colMap['name'], $colMap['departure_time'], $colMap['start_date'], $colMap['end_date'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Tên, Giờ đi, Ngày bắt đầu, Ngày kết thúc).']],
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
                $code = $payload['code'] ?? null;
                if ($code && TpProgram::withTrashed()->where('code', $code)->exists()) {
                    $skipped++;

                    continue;
                }

                $this->createAction->execute($payload, $actorId);
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
            if (isset($map['name'])) {
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
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Thiếu tên chương trình.');
        }

        $departure = $this->parseTime($row['departure_time'] ?? '');
        $return = isset($row['return_time']) && trim((string) $row['return_time']) !== ''
            ? $this->parseTime($row['return_time'])
            : null;
        $start = $this->parseDate($row['start_date'] ?? '');
        $end = $this->parseDate($row['end_date'] ?? '');

        if ($end->lt($start)) {
            throw new \InvalidArgumentException('Ngày kết thúc phải sau ngày bắt đầu.');
        }

        $payload = [
            'name' => $name,
            'departure_time' => $departure,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'runs_on' => $this->parseRunsOn($row['runs_on'] ?? ''),
        ];

        $code = trim((string) ($row['code'] ?? ''));
        if ($code !== '') {
            $payload['code'] = $code;
        }
        foreach (['origin_name', 'destination_name', 'notes'] as $k) {
            $v = trim((string) ($row[$k] ?? ''));
            if ($v !== '') {
                $payload[$k] = $v;
            }
        }
        if ($return) {
            $payload['return_time'] = $return;
        }
        $cost = trim((string) ($row['cost_per_trip'] ?? ''));
        if ($cost !== '' && is_numeric(str_replace([',', ' '], '', $cost))) {
            $payload['cost_per_trip'] = (float) str_replace([',', ' '], '', $cost);
        }

        return $payload;
    }

    private function parseTime(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            throw new \InvalidArgumentException('Thiếu giờ đi.');
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $v)) {
            [$h, $m] = array_map('intval', explode(':', $v, 2));

            return sprintf('%02d:%02d', $h, $m);
        }
        try {
            return Carbon::parse($v)->format('H:i');
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Giờ không hợp lệ: '.$v);
        }
    }

    private function parseDate(string $value): Carbon
    {
        $v = trim($value);
        if ($v === '') {
            throw new \InvalidArgumentException('Thiếu ngày.');
        }
        try {
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $v)) {
                return Carbon::createFromFormat('d/m/Y', $v)->startOfDay();
            }

            return Carbon::parse($v)->startOfDay();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Ngày không hợp lệ: '.$v);
        }
    }

    /**
     * @return list<string>
     */
    private function parseRunsOn(string $value): array
    {
        $v = trim($value);
        if ($v === '') {
            return ['mon', 'tue', 'wed', 'thu', 'fri'];
        }
        $parts = array_filter(array_map('trim', explode(',', strtolower($v))));
        $allowed = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $runs = array_values(array_intersect($parts, $allowed));

        return $runs !== [] ? $runs : ['mon', 'tue', 'wed', 'thu', 'fri'];
    }
}
