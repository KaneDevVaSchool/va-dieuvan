<?php

namespace App\Services\TpStudent;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TpStudentListXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const META_BG = 'FFFDF2F5';

    /** @var list<string> */
    private const HEADERS = [
        'STT',
        'Họ tên',
        'Mã học sinh',
        'Khối',
        'Lớp',
        'Giới tính',
        'Ngày sinh',
        'Chương trình',
        'Mã CT',
        'Trạng thái vận chuyển',
        'Phụ huynh',
        'SĐT phụ huynh',
        'Điểm đón',
        'Địa chỉ',
        'Ghi chú',
    ];

    /**
     * @param  list<array<string, mixed>>  $rows  Output of TpStudentController::presentStudent
     * @param  array{exported_at?: string, exported_by?: string|null, filter_summary?: string, total?: int}  $meta
     */
    public function writeTempFile(array $rows, array $meta = []): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Danh sách học sinh đưa đón')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách học sinh');
        $this->buildSheet($sheet, $rows, $meta);

        $tmpPath = tempnam(sys_get_temp_dir(), 'tp_students_export_');
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
        $ws->setCellValue('A1', 'DANH SÁCH HỌC SINH ĐƯA ĐÓN');
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
        $ws->setCellValue('A3', "Tổng: {$total} học sinh  ·  Bộ lọc: {$filterSummary}");

        $ws->getStyle("A2:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::META_BG, 2)]],
            'font' => ['size' => 10, 'color' => ['rgb' => '475569']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $ws->getRowDimension(2)->setRowHeight(20);
        $ws->getRowDimension(3)->setRowHeight(20);

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
        $ws->getRowDimension($headerRow)->setRowHeight(24);

        $dataStart = $headerRow + 1;
        foreach ($rows as $offset => $row) {
            $r = $dataStart + $offset;
            $program = is_array($row['program'] ?? null) ? $row['program'] : null;
            $values = [
                $offset + 1,
                $row['full_name'] ?? '',
                $row['code'] ?? '',
                $row['grade'] ?? '',
                $row['class_name'] ?? '',
                $this->genderLabel($row['gender'] ?? null),
                $this->formatDate($row['date_of_birth'] ?? null),
                $program['name'] ?? '',
                $program['code'] ?? '',
                $this->transportLabel($row['transport_status'] ?? null),
                $row['parent_name'] ?? '',
                $row['parent_phone'] ?? '',
                $row['pickup_point'] ?? '',
                $row['address'] ?? '',
                $row['note'] ?? '',
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
            $ws->getStyle("A{$dataStart}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $widths = [6, 28, 14, 8, 10, 10, 12, 26, 12, 18, 22, 16, 28, 36, 24];
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

    private function genderLabel(mixed $gender): string
    {
        if ($gender === null || $gender === '') {
            return '';
        }
        $g = strtolower((string) $gender);

        return match ($g) {
            'male', 'nam', 'm' => 'Nam',
            'female', 'nu', 'nữ', 'f' => 'Nữ',
            default => (string) $gender,
        };
    }

    private function transportLabel(mixed $status): string
    {
        return match ((string) $status) {
            'transporting' => 'Đang đưa đón',
            'pending' => 'Chờ duyệt',
            'paused' => 'Tạm dừng',
            'unregistered' => 'Chưa đăng ký',
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
}
