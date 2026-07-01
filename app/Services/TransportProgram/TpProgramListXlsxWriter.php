<?php

namespace App\Services\TransportProgram;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TpProgramListXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const META_BG = 'FFFDF2F5';

    /** @var list<string> */
    private const HEADERS = [
        'STT',
        'Mã CT',
        'Tên chương trình',
        'Trạng thái',
        'Điểm đi',
        'Điểm đến',
        'Giờ đi',
        'Giờ về',
        'Ngày bắt đầu',
        'Ngày kết thúc',
        'Buổi/tuần',
        'Số ngày',
        'Học sinh',
        'Phụ trách',
        'Chi phí/chuyến',
        'Ghi chú',
    ];

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{exported_at?: string, exported_by?: string|null, filter_summary?: string, total?: int}  $meta
     */
    public function writeTempFile(array $rows, array $meta = []): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Danh sách chương trình đưa đón')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chương trình');
        $this->buildSheet($sheet, $rows, $meta);

        $tmpPath = tempnam(sys_get_temp_dir(), 'tp_programs_export_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function buildSheet(Worksheet $ws, array $rows, array $meta): void
    {
        $lastCol = $this->colLetter(count(self::HEADERS) - 1);
        $lastColIdx = count(self::HEADERS) - 1;

        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValue('A1', 'DANH SÁCH CHƯƠNG TRÌNH ĐƯA ĐÓN');
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
        $ws->setCellValue('A3', "Tổng: {$total} chương trình  ·  Bộ lọc: {$filterSummary}");

        $ws->getStyle("A2:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::META_BG, 2)]],
            'font' => ['size' => 10, 'color' => ['rgb' => '475569']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
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
            $runsOn = is_array($row['runs_on'] ?? null) ? count($row['runs_on']) : 0;
            $values = [
                $offset + 1,
                $row['code'] ?? '',
                $row['name'] ?? '',
                $this->statusLabel($row['status'] ?? null),
                $row['origin_name'] ?? '',
                $row['destination_name'] ?? '',
                $this->formatTime($row['departure_time'] ?? null),
                $this->formatTime($row['return_time'] ?? null),
                $this->formatDate($row['start_date'] ?? null),
                $this->formatDate($row['end_date'] ?? null),
                $runsOn,
                $row['day_count'] ?? 0,
                $row['enrolled_count'] ?? 0,
                $row['responsible_user_name'] ?? '',
                $row['cost_per_trip'] ?? '',
                $row['notes'] ?? '',
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
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        $widths = [6, 12, 28, 14, 20, 20, 10, 10, 12, 12, 10, 10, 10, 22, 14, 24];
        foreach ($widths as $i => $w) {
            if ($i <= $lastColIdx) {
                $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
            }
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
            'draft' => 'Nháp',
            'active' => 'Hoạt động',
            'paused' => 'Tạm dừng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => (string) $status,
        };
    }

    private function formatTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return substr((string) $value, 0, 5);
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
}
