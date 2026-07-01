<?php

namespace App\Services\Cargo;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CargoListXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const META_BG = 'FFFDF2F5';

    /** @var list<string> */
    private const HEADERS = [
        'STT', 'Mã đơn hàng', 'Người gửi', 'Người nhận',
        'Địa chỉ lấy hàng', 'Địa chỉ giao hàng', 'Khối lượng (g)',
        'Số kiện', 'Trạng thái', 'Hạn giao', 'Ngày tạo',
    ];

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{exported_at?: string, exported_by?: string|null, filter_summary?: string, total?: int}  $meta
     */
    public function writeTempFile(array $rows, array $meta = []): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Danh sách đơn hàng')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Đơn hàng');
        $this->buildSheet($sheet, $rows, $meta);

        $tmpPath = tempnam(sys_get_temp_dir(), 'cargo_export_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function buildSheet(Worksheet $ws, array $rows, array $meta): void
    {
        $lastCol = $this->colLetter(count(self::HEADERS) - 1);

        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValue('A1', 'DANH SÁCH ĐƠN HÀNG VẬN CHUYỂN');
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::VA_RED, 2)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);

        $exportedAt = $meta['exported_at'] ?? now()->format('d/m/Y H:i');
        $exportedBy = trim((string) ($meta['exported_by'] ?? ''));
        $total = (int) ($meta['total'] ?? count($rows));
        $filterSummary = trim((string) ($meta['filter_summary'] ?? 'Tất cả'));

        $ws->mergeCells("A2:{$lastCol}2");
        $line2 = "Xuất lúc: {$exportedAt}";
        if ($exportedBy !== '') {
            $line2 .= "  ·  Người xuất: {$exportedBy}";
        }
        $ws->setCellValue('A2', $line2);

        $ws->mergeCells("A3:{$lastCol}3");
        $ws->setCellValue('A3', "Tổng: {$total} đơn hàng  ·  Bộ lọc: {$filterSummary}");

        $ws->getStyle("A2:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::META_BG, 2)]],
            'font' => ['size' => 10, 'color' => ['rgb' => '475569']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $headerRow = 5;
        foreach (self::HEADERS as $i => $label) {
            $ws->setCellValue($this->colLetter($i).$headerRow, $label);
        }
        $ws->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::HEADER_BG, 2)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);

        $dataStart = $headerRow + 1;
        foreach ($rows as $offset => $row) {
            $r = $dataStart + $offset;
            $values = [
                $offset + 1,
                $row['tracking_code'] ?? '',
                $row['sender_name'] ?? '',
                $row['receiver_name'] ?? '',
                $row['pickup_address'] ?? '',
                $row['delivery_address'] ?? '',
                $row['weight_grams'] ?? '',
                $row['quantity'] ?? '',
                $this->statusLabel($row['status'] ?? null),
                $this->formatDate($row['sla_due_at'] ?? null),
                $this->formatDateTime($row['created_at'] ?? null),
            ];
            foreach ($values as $i => $value) {
                $ws->setCellValue($this->colLetter($i).$r, $value);
            }
            if ($offset % 2 === 1) {
                $ws->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::ZEBRA_BG, 2)]],
                ]);
            }
        }

        $lastDataRow = max($dataStart, $dataStart + count($rows) - 1);
        if (count($rows) > 0) {
            $ws->getStyle("A{$dataStart}:{$lastCol}{$lastDataRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        $widths = [6, 14, 22, 22, 28, 28, 14, 10, 14, 14, 16];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }

        $ws->freezePane('A'.($headerRow + 1));
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        $n = $index;
        do {
            $letter = chr(ord('A') + ($n % 26)).$letter;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $letter;
    }

    private function statusLabel(mixed $status): string
    {
        return match ((string) $status) {
            'pending' => 'Chờ lấy hàng',
            'picked_up' => 'Đã lấy hàng',
            'in_transit' => 'Đang giao',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy',
            default => (string) $status,
        };
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse((string) $value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse((string) $value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
