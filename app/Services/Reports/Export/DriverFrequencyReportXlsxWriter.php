<?php

namespace App\Services\Reports\Export;

use App\Services\Reports\DriverFrequencyReportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DriverFrequencyReportXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const TYPE_LABELS = ['Điểm-điểm', 'Công tác', 'Đưa đón', 'Hàng hóa'];

    /**
     * @param  array<string, mixed>  $payload  Output of DriverFrequencyReportService::report()
     * @param  array<string, mixed>  $filters
     */
    public function writeTempFile(array $payload, array $filters = [], ?string $exportedBy = null): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Báo cáo tần suất tài xế & xe')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Tần suất tài xế');
        $this->buildSheet($ws, $payload, $filters, $exportedBy);

        $tmpPath = tempnam(sys_get_temp_dir(), 'drv_freq_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    /** @param  array<string, mixed>  $payload */
    private function buildSheet(Worksheet $ws, array $payload, array $filters, ?string $exportedBy): void
    {
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(28);
        $ws->getColumnDimension('D')->setWidth(10);
        $ws->getColumnDimension('E')->setWidth(10);
        $ws->getColumnDimension('F')->setWidth(12);
        $ws->getColumnDimension('G')->setWidth(10);
        $ws->getColumnDimension('H')->setWidth(10);
        $ws->getColumnDimension('I')->setWidth(10);
        $ws->getColumnDimension('J')->setWidth(10);
        $ws->getColumnDimension('K')->setWidth(10);
        $ws->getColumnDimension('L')->setWidth(12);

        $row = 2;
        $ws->mergeCells("B{$row}:L{$row}");
        $ws->setCellValue("B{$row}", 'BÁO CÁO TẦN SUẤT TÀI XẾ & XE');
        $this->applyStyle($ws, "B{$row}:L{$row}", [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::VA_RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;
        $ws->mergeCells("B{$row}:L{$row}");
        $ws->setCellValue("B{$row}", 'Hệ Thống Điều Vận Nội Bộ  ·  Vietnam America Schools');
        $this->applyStyle($ws, "B{$row}:L{$row}", [
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row += 2;
        $year = $payload['year'] ?? ($filters['year'] ?? now()->year);
        $filterLine = 'Năm '.$year;
        if (! empty($filters['quarter'])) {
            $filterLine .= ' · '.strtoupper($filters['quarter']);
        }
        if (! empty($filters['trip_type'])) {
            $map = DriverFrequencyReportService::TRIP_TYPE_LABELS;
            $filterLine .= ' · '.($map[$filters['trip_type']] ?? $filters['trip_type']);
        }
        $ws->mergeCells("B{$row}:L{$row}");
        $ws->setCellValue("B{$row}", $filterLine);
        $this->applyStyle($ws, "B{$row}:L{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDF2F5']],
            'font' => ['size' => 9, 'color' => ['argb' => 'FF7D0029']],
        ]);

        $row++;
        $exportedAt = now()->format('d/m/Y H:i');
        $ws->setCellValue("B{$row}", 'Xuất lúc: '.$exportedAt);
        if ($exportedBy) {
            $ws->setCellValue("F{$row}", 'Người xuất: '.$exportedBy);
        }

        $kpi = $payload['kpi'] ?? [];
        $row += 2;
        $ws->mergeCells("B{$row}:L{$row}");
        $ws->setCellValue("B{$row}", 'TỔNG QUAN');
        $this->applyStyle($ws, "B{$row}:L{$row}", [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::VA_RED]],
        ]);

        $row++;
        $summary = sprintf(
            'Tổng chuyến: %s  |  Tài xế hoạt động: %s  |  Xe hoạt động: %s  |  Tổng giờ lái: %sh  |  Đúng giờ: %s%%',
            number_format((int) ($kpi['totalTrips'] ?? 0), 0, ',', '.'),
            (int) ($kpi['activeDrivers'] ?? 0),
            (int) ($kpi['activeVehicles'] ?? 0),
            number_format((float) ($kpi['totalHours'] ?? 0), 1, ',', '.'),
            number_format((float) ($kpi['overallOnTime'] ?? 0), 1, ',', '.'),
        );
        $ws->mergeCells("B{$row}:L{$row}");
        $ws->setCellValue("B{$row}", $summary);
        $this->applyStyle($ws, "B{$row}:L{$row}", [
            'font' => ['size' => 9],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
        ]);

        $row += 2;
        $headerRow = $row;
        $headers = ['#', 'Mã', 'Tài xế', 'Chuyến', 'Giờ lái', 'Đúng giờ %', ...self::TYPE_LABELS, 'KPI', 'Xét thưởng'];
        $cols = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        $ws->getColumnDimension('M')->setWidth(12);
        foreach ($headers as $i => $label) {
            $ws->setCellValue($cols[$i].$headerRow, $label);
        }
        $this->applyStyle($ws, 'B'.$headerRow.':M'.$headerRow, [
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $drivers = $payload['drivers'] ?? [];
        $rank = 1;
        foreach ($drivers as $driver) {
            $row++;
            $types = $driver['types'] ?? [0, 0, 0, 0];
            $bonusMap = ['A' => 'Thưởng A', 'B' => 'Thưởng B', 'C' => 'Thưởng C'];
            $bonus = $bonusMap[$driver['bonus'] ?? 'C'] ?? 'Thưởng C';

            $values = [
                $rank,
                $driver['code'] ?? '',
                $driver['name'] ?? '',
                (int) ($driver['trips'] ?? 0),
                (float) ($driver['hours'] ?? 0),
                (float) ($driver['onTime'] ?? 0),
                (int) ($types[0] ?? 0),
                (int) ($types[1] ?? 0),
                (int) ($types[2] ?? 0),
                (int) ($types[3] ?? 0),
                (int) ($driver['kpi'] ?? 0),
                $bonus,
            ];

            foreach ($values as $i => $val) {
                $ws->setCellValue($cols[$i].$row, $val);
            }

            if ($rank % 2 === 0) {
                $this->applyStyle($ws, 'B'.$row.':M'.$row, [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::ZEBRA_BG]],
                ]);
            }
            $rank++;
        }

        $this->applyStyle($ws, 'B'.$headerRow.':M'.$row, [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
        ]);

        $row += 2;
        $ws->mergeCells("B{$row}:M{$row}");
        $ws->setCellValue("B{$row}", '* Điểm KPI = 50% tần suất + 30% đúng giờ + 20% đa dạng loại chuyến');
        $this->applyStyle($ws, "B{$row}:M{$row}", [
            'font' => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF888888']],
        ]);

        $vehicles = $payload['vehicles'] ?? [];
        if ($vehicles !== []) {
            $row += 2;
            $ws->mergeCells("B{$row}:E{$row}");
            $ws->setCellValue("B{$row}", 'TẦN SUẤT THEO XE');
            $this->applyStyle($ws, "B{$row}:E{$row}", [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::VA_RED]],
            ]);
            $row++;
            $ws->setCellValue('B'.$row, 'Biển số');
            $ws->setCellValue('C'.$row, 'Số chuyến');
            $this->applyStyle($ws, 'B'.$row.':C'.$row, [
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            ]);
            foreach ($vehicles as $vehicle) {
                $row++;
                $ws->setCellValue('B'.$row, $vehicle['plate'] ?? '');
                $ws->setCellValue('C'.$row, (int) ($vehicle['trips'] ?? 0));
            }
        }
    }

    /** @param  array<string, mixed>  $styleArr */
    private function applyStyle(Worksheet $ws, string $range, array $styleArr): void
    {
        $style = $ws->getStyle($range);

        if (isset($styleArr['font'])) {
            $f = $styleArr['font'];
            isset($f['bold']) && $style->getFont()->setBold($f['bold']);
            isset($f['italic']) && $style->getFont()->setItalic($f['italic']);
            isset($f['size']) && $style->getFont()->setSize($f['size']);
            if (isset($f['color']['argb'])) {
                $style->getFont()->getColor()->setARGB($f['color']['argb']);
            }
        }

        if (isset($styleArr['fill'])) {
            $fi = $styleArr['fill'];
            $style->getFill()->setFillType($fi['fillType'] ?? Fill::FILL_SOLID);
            if (isset($fi['startColor']['argb'])) {
                $style->getFill()->getStartColor()->setARGB($fi['startColor']['argb']);
            }
        }

        if (isset($styleArr['alignment'])) {
            $al = $styleArr['alignment'];
            isset($al['horizontal']) && $style->getAlignment()->setHorizontal($al['horizontal']);
            isset($al['vertical']) && $style->getAlignment()->setVertical($al['vertical']);
        }

        if (isset($styleArr['borders'])) {
            $style->applyFromArray(['borders' => $styleArr['borders']]);
        }
    }
}
