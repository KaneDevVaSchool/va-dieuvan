<?php

namespace App\Services\Cargo;

use App\Models\CargoShipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CargoSpreadsheetImportService
{
    /** @var array<string, string> */
    private const HEADER_MAP = [
        'mã đơn hàng' => 'tracking_code',
        'ma don hang' => 'tracking_code',
        'tên người gửi' => 'sender_name',
        'ten nguoi gui' => 'sender_name',
        'tên người nhận' => 'receiver_name',
        'ten nguoi nhan' => 'receiver_name',
        'địa chỉ lấy hàng' => 'pickup_address',
        'dia chi lay hang' => 'pickup_address',
        'địa chỉ giao hàng' => 'delivery_address',
        'dia chi giao hang' => 'delivery_address',
        'khối lượng (gram)' => 'weight_grams',
        'khoi luong (gram)' => 'weight_grams',
        'khối lượng' => 'weight_grams',
        'khoi luong' => 'weight_grams',
        'số lượng kiện' => 'quantity',
        'so luong kien' => 'quantity',
        'số lượng' => 'quantity',
        'so luong' => 'quantity',
        'hạn giao (sla)' => 'sla_due_at',
        'han giao (sla)' => 'sla_due_at',
        'hạn giao' => 'sla_due_at',
        'han giao' => 'sla_due_at',
        'trạng thái' => 'status',
        'trang thai' => 'status',
    ];

    /** @var array<string, string> */
    private const STATUS_MAP = [
        'pending' => 'pending',
        'chờ' => 'pending',
        'cho' => 'pending',
        'picked_up' => 'picked_up',
        'đã lấy hàng' => 'picked_up',
        'da lay hang' => 'picked_up',
        'in_transit' => 'in_transit',
        'đang giao' => 'in_transit',
        'dang giao' => 'in_transit',
        'delivered' => 'delivered',
        'đã giao' => 'delivered',
        'da giao' => 'delivered',
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
        if (! isset($colMap['sender_name'], $colMap['receiver_name'], $colMap['pickup_address'], $colMap['delivery_address'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [['row' => $headerRow, 'message' => 'Thiếu cột bắt buộc (Tên người gửi, Tên người nhận, Địa chỉ lấy hàng, Địa chỉ giao hàng).']],
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

                $trackingCode = $payload['tracking_code'] ?? null;
                if ($trackingCode && CargoShipment::where('tracking_code', $trackingCode)->exists()) {
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use ($payload) {
                    $shipment = CargoShipment::create($payload);
                    if (! $shipment->tracking_code) {
                        $shipment->update([
                            'tracking_code' => 'CGO-'.str_pad((string) $shipment->id, 8, '0', STR_PAD_LEFT),
                        ]);
                    }
                });

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
            if (isset($map['sender_name']) || isset($map['receiver_name'])) {
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
        $senderName = trim((string) ($row['sender_name'] ?? ''));
        $receiverName = trim((string) ($row['receiver_name'] ?? ''));
        $pickupAddress = trim((string) ($row['pickup_address'] ?? ''));
        $deliveryAddress = trim((string) ($row['delivery_address'] ?? ''));

        if ($senderName === '') {
            throw new \InvalidArgumentException('Thiếu tên người gửi.');
        }
        if ($receiverName === '') {
            throw new \InvalidArgumentException('Thiếu tên người nhận.');
        }
        if ($pickupAddress === '') {
            throw new \InvalidArgumentException('Thiếu địa chỉ lấy hàng.');
        }
        if ($deliveryAddress === '') {
            throw new \InvalidArgumentException('Thiếu địa chỉ giao hàng.');
        }

        $statusRaw = mb_strtolower(trim((string) ($row['status'] ?? '')));
        $status = $statusRaw !== '' ? (self::STATUS_MAP[$statusRaw] ?? 'pending') : 'pending';

        $payload = [
            'sender_name' => $senderName,
            'receiver_name' => $receiverName,
            'pickup_address' => $pickupAddress,
            'delivery_address' => $deliveryAddress,
            'status' => $status,
            'sla_due_at' => now()->addHours(3),
        ];

        $trackingCode = trim((string) ($row['tracking_code'] ?? ''));
        if ($trackingCode !== '') {
            $payload['tracking_code'] = $trackingCode;
        }

        $weight = trim((string) ($row['weight_grams'] ?? ''));
        if ($weight !== '' && is_numeric(str_replace([',', ' '], '', $weight))) {
            $payload['weight_grams'] = (int) str_replace([',', ' '], '', $weight);
        }

        $qty = trim((string) ($row['quantity'] ?? ''));
        if ($qty !== '' && is_numeric($qty)) {
            $payload['quantity'] = (int) $qty;
        }

        $slaRaw = trim((string) ($row['sla_due_at'] ?? ''));
        if ($slaRaw !== '') {
            try {
                $payload['sla_due_at'] = Carbon::parse($slaRaw)->endOfDay();
            } catch (\Throwable) {
                // keep default
            }
        }

        return $payload;
    }
}
