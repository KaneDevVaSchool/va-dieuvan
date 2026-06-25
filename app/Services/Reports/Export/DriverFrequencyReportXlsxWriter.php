<?php

namespace App\Services\Reports\Export;

use App\Services\Reports\DriverFrequencyReportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Xuất XLSX báo cáo tần suất theo mẫu docs/TanSuat_TaiXe_Xe.xlsx (4 sheet).
 */
class DriverFrequencyReportXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const TOTAL_BG = 'FFF5F5F5';

    private const SIG_BG = 'FFEDEDED';

    private const NUM_FMT = '#,##0';

    private const DEC_FMT = '#,##0.0';

    private const PCT_FMT = '0.00';

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $filters
     */
    public function writeTempFile(array $payload, array $filters = [], ?string $exportedBy = null): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Báo cáo tần suất tài xế & xe')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $meta = $this->buildMeta($payload, $filters, $exportedBy);

        $ws1 = $spreadsheet->getActiveSheet();
        $ws1->setTitle('Xếp hạng tài xế');
        $this->buildDriverRankingSheet($ws1, $payload, $meta);

        $ws2 = $spreadsheet->createSheet();
        $ws2->setTitle('Tần suất xe');
        $this->buildVehicleSheet($ws2, $payload, $meta);

        $ws3 = $spreadsheet->createSheet();
        $ws3->setTitle('Theo dõi theo tháng');
        $this->buildMonthlySheet($ws3, $payload, $meta);

        $ws4 = $spreadsheet->createSheet();
        $ws4->setTitle('Tổng hợp xét thưởng');
        $this->buildBonusSheet($ws4, $payload, $meta);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'drv_freq_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $filters
     * @return array{periodLine: string, exportDate: string, exportedBy: string, approvalBlank: string}
     */
    private function buildMeta(array $payload, array $filters, ?string $exportedBy): array
    {
        $year = (int) ($payload['year'] ?? ($filters['year'] ?? now()->year));
        $periodLine = 'Kỳ báo cáo: Năm '.$year;
        if (! empty($filters['month'])) {
            $periodLine .= ' · Tháng '.((int) $filters['month']);
        } elseif (! empty($filters['quarter'])) {
            $qMap = ['q1' => 'Quý 1', 'q2' => 'Quý 2', 'q3' => 'Quý 3', 'q4' => 'Quý 4'];
            $periodLine .= ' · '.($qMap[$filters['quarter']] ?? strtoupper((string) $filters['quarter']));
        }
        if (! empty($filters['trip_type'])) {
            $map = DriverFrequencyReportService::TRIP_TYPE_LABELS;
            $periodLine .= ' · '.($map[$filters['trip_type']] ?? $filters['trip_type']);
        }
        if (! empty($filters['vehicle_plate'])) {
            $periodLine .= ' · Biển số '.$filters['vehicle_plate'];
        }

        return [
            'periodLine' => $periodLine,
            'exportDate' => now()->format('d/m/Y'),
            'exportedBy' => $exportedBy ?? '_______________________________',
            'approvalBlank' => '_______________________________',
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function buildDriverRankingSheet(Worksheet $ws, array $payload, array $meta): void
    {
        $this->setColumnWidths($ws, [
            'A' => 6, 'B' => 24, 'C' => 10, 'D' => 12, 'E' => 10, 'F' => 14,
            'G' => 12, 'H' => 12, 'I' => 12, 'J' => 12, 'K' => 16,
        ]);

        $headerRow = $this->writeSheetHeader(
            $ws,
            'BÁO CÁO TẦN SUẤT TÀI XẾ',
            $meta,
            'K',
            withApproval: true,
        );

        $headers = [
            'A' => 'Hạng',
            'B' => 'Tài xế',
            'C' => 'Mã NV',
            'D' => 'Tổng chuyến',
            'E' => 'Giờ lái',
            'F' => 'TB chuyến/tháng',
            'G' => 'Chuyến Công tác',
            'H' => 'Chuyến D2D',
            'I' => 'Chuyến P2P',
            'J' => 'Chuyến Hàng hóa',
            'K' => 'Xếp loại thưởng',
        ];
        $this->writeTableHeader($ws, $headerRow, $headers, 'A', 'K');

        $drivers = $payload['drivers'] ?? [];
        $dataStart = $headerRow + 1;
        foreach ($drivers as $i => $driver) {
            $r = $dataStart + $i;
            $types = $driver['typesExport'] ?? [0, 0, 0, 0];
            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $driver['name'] ?? '');
            $ws->setCellValue("C{$r}", $driver['employeeCode'] ?? ($driver['code'] ?? ''));
            $ws->setCellValue("D{$r}", (int) ($driver['trips'] ?? 0));
            $ws->setCellValue("E{$r}", (float) ($driver['hours'] ?? 0));
            $ws->setCellValue("F{$r}", (float) ($driver['avgTripsPerMonth'] ?? 0));
            $ws->setCellValue("G{$r}", (int) ($types[0] ?? 0));
            $ws->setCellValue("H{$r}", (int) ($types[1] ?? 0));
            $ws->setCellValue("I{$r}", (int) ($types[2] ?? 0));
            $ws->setCellValue("J{$r}", (int) ($types[3] ?? 0));
            $ws->setCellValue("K{$r}", $driver['bonusLabel'] ?? 'Thưởng C');

            $this->styleDataRow($ws, "A{$r}:K{$r}", $i % 2 === 1);
            $ws->getStyle("D{$r}:J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $ws->getStyle("E{$r}:F{$r}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
            $ws->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle("K{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $totalRow = $dataStart + count($drivers);
        if ($drivers !== []) {
            $this->writeDriverTotalsRow($ws, $totalRow, $drivers, 'A', 'K');
            $this->applyBorders($ws, 'A'.$headerRow.':K'.$totalRow);
            $this->writeSignatureBlock($ws, $totalRow + 2, 'A', 'K');
        } else {
            $r = $dataStart;
            $ws->mergeCells("A{$r}:K{$r}");
            $ws->setCellValue("A{$r}", 'Không có dữ liệu trong kỳ đã chọn.');
            $this->applyStyle($ws, "A{$r}:K{$r}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            ]);
        }

        $ws->freezePane('A'.($headerRow + 1));
    }

    /** @param  array<string, mixed>  $payload */
    private function buildVehicleSheet(Worksheet $ws, array $payload, array $meta): void
    {
        $this->setColumnWidths($ws, [
            'A' => 6, 'B' => 14, 'C' => 12, 'D' => 22, 'E' => 12, 'F' => 12,
            'G' => 12, 'H' => 14, 'I' => 14, 'J' => 14,
        ]);

        $headerRow = $this->writeSheetHeader($ws, 'TẦN SUẤT SỬ DỤNG XE', $meta, 'J');

        $headers = [
            'A' => 'STT',
            'B' => 'Biển số',
            'C' => 'Loại xe',
            'D' => 'Hãng / Model',
            'E' => 'Tổng chuyến',
            'F' => 'Tổng km',
            'G' => 'Giờ vận hành',
            'H' => 'TB chuyến/tháng',
            'I' => 'Tỷ lệ sử dụng (%)',
            'J' => 'Trạng thái',
        ];
        $this->writeTableHeader($ws, $headerRow, $headers, 'A', 'J');

        $vehicles = $payload['vehicles'] ?? [];
        $dataStart = $headerRow + 1;
        foreach ($vehicles as $i => $vehicle) {
            $r = $dataStart + $i;
            $util = (float) ($vehicle['utilization'] ?? 0);
            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $vehicle['plate'] ?? '');
            $ws->setCellValue("C{$r}", $vehicle['category'] ?? 'Xe nội bộ');
            $ws->setCellValue("D{$r}", $vehicle['model'] ?? '—');
            $ws->setCellValue("E{$r}", (int) ($vehicle['trips'] ?? 0));
            $ws->setCellValue("F{$r}", (int) ($vehicle['totalKm'] ?? 0));
            $ws->setCellValue("G{$r}", (float) ($vehicle['hours'] ?? 0));
            $ws->setCellValue("H{$r}", (float) ($vehicle['avgTripsPerMonth'] ?? 0));
            $ws->setCellValue("I{$r}", $util);
            $ws->setCellValue("J{$r}", $vehicle['statusLabel'] ?? '');

            $this->styleDataRow($ws, "A{$r}:J{$r}", $i % 2 === 1);
            $ws->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle("E{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $ws->getStyle("G{$r}:H{$r}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
            $ws->getStyle("F{$r}")->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $ws->getStyle("I{$r}")->getNumberFormat()->setFormatCode(self::PCT_FMT);
            $ws->getStyle("J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $totalRow = $dataStart + count($vehicles);
        if ($vehicles !== []) {
            $this->writeVehicleTotalsRow($ws, $totalRow, $vehicles);
            $this->applyBorders($ws, 'A'.$headerRow.':J'.$totalRow);
            $this->writeSignatureBlock($ws, $totalRow + 2, 'A', 'J');
        }

        $ws->freezePane('A'.($headerRow + 1));
    }

    /** @param  array<string, mixed>  $payload */
    private function buildMonthlySheet(Worksheet $ws, array $payload, array $meta): void
    {
        $this->setColumnWidths($ws, [
            'A' => 22, 'B' => 8, 'C' => 8, 'D' => 8, 'E' => 8, 'F' => 8,
            'G' => 8, 'H' => 8, 'I' => 8, 'J' => 8, 'K' => 8, 'L' => 8, 'M' => 8, 'N' => 10,
        ]);

        $headerRow = $this->writeSheetHeader($ws, 'THEO DÕI CHUYẾN THEO THÁNG', $meta, 'N');

        $headers = ['A' => 'Tài xế'];
        foreach (range(1, 12) as $m) {
            $col = $this->columnLetter($m + 1);
            $headers[$col] = 'Tháng '.$m;
        }
        $headers['N'] = 'Tổng năm';
        $this->writeTableHeader($ws, $headerRow, $headers, 'A', 'N');

        $rows = $payload['driver_monthly'] ?? [];
        $dataStart = $headerRow + 1;
        foreach ($rows as $i => $row) {
            $r = $dataStart + $i;
            $months = $row['months'] ?? array_fill(0, 12, 0);
            $ws->setCellValue("A{$r}", $row['name'] ?? '');
            foreach (range(0, 11) as $mi) {
                $col = $this->columnLetter($mi + 2);
                $ws->setCellValue($col.$r, (int) ($months[$mi] ?? 0));
            }
            $ws->setCellValue("N{$r}", (int) ($row['yearTotal'] ?? 0));
            $this->styleDataRow($ws, "A{$r}:N{$r}", $i % 2 === 1);
            $ws->getStyle("B{$r}:N{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $ws->getStyle("B{$r}:N{$r}")->getNumberFormat()->setFormatCode(self::NUM_FMT);
        }

        $totalRow = $dataStart + count($rows);
        if ($rows !== []) {
            $monthlyTotals = $payload['monthly_totals'] ?? array_fill(0, 12, 0);
            $ws->setCellValue("A{$totalRow}", 'TỔNG THÁNG');
            foreach (range(0, 11) as $mi) {
                $col = $this->columnLetter($mi + 2);
                $ws->setCellValue($col.$totalRow, (int) ($monthlyTotals[$mi] ?? 0));
            }
            $ws->setCellValue('N'.$totalRow, array_sum($monthlyTotals));
            $this->applyStyle($ws, "A{$totalRow}:N{$totalRow}", [
                'font' => ['bold' => true, 'size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
            ]);
            $ws->getStyle("B{$totalRow}:N{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorders($ws, 'A'.$headerRow.':N'.$totalRow);
        }

        $ws->freezePane('B'.($headerRow + 1));
    }

    /** @param  array<string, mixed>  $payload */
    private function buildBonusSheet(Worksheet $ws, array $payload, array $meta): void
    {
        $this->setColumnWidths($ws, [
            'A' => 6, 'B' => 24, 'C' => 10, 'D' => 12, 'E' => 10,
            'F' => 14, 'G' => 14, 'H' => 14, 'I' => 22,
        ]);

        $ws->getRowDimension(1)->setRowHeight(6);
        $ws->mergeCells('C2:I2');
        $ws->setCellValue('C2', 'TỔNG HỢP XÉT THƯỞNG CUỐI NĂM');
        $this->applyStyle($ws, 'C2:I2', [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::VA_RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ws->mergeCells('C3:I3');
        $ws->setCellValue('C3', 'Hệ Thống Điều Vận Nội Bộ  ·  Vietnam America Schools');
        $this->applyStyle($ws, 'C3:I3', [
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ws->mergeCells('A5:I5');
        $ws->setCellValue(
            'A5',
            $meta['periodLine'].'   |   Ngày xuất: '.$meta['exportDate']
            .'   |   Căn cứ: 50% tần suất + 30% đúng giờ + 20% đa dạng chuyến',
        );
        $this->applyStyle($ws, 'A5:I5', [
            'font' => ['size' => 8, 'color' => ['argb' => 'FF444444']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDF2F5']],
        ]);

        $headerRow = 6;
        $ws->getRowDimension($headerRow)->setRowHeight(28);
        $headers = [
            'A' => 'Hạng',
            'B' => 'Tài xế',
            'C' => 'Mã NV',
            'D' => 'Tổng chuyến',
            'E' => 'Giờ lái',
            'F' => "Điểm tần suất\n(50%)",
            'G' => "Điểm đa dạng\n(20%)",
            'H' => 'XẾP LOẠI',
            'I' => 'GHI CHÚ',
        ];
        foreach ($headers as $col => $label) {
            $ws->setCellValue($col.$headerRow, $label);
        }
        $this->applyStyle($ws, 'A'.$headerRow.':I'.$headerRow, [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF555577']]],
        ]);

        $drivers = $payload['drivers'] ?? [];
        $dataStart = $headerRow + 1;
        foreach ($drivers as $i => $driver) {
            $r = $dataStart + $i;
            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $driver['name'] ?? '');
            $ws->setCellValue("C{$r}", $driver['employeeCode'] ?? '');
            $ws->setCellValue("D{$r}", (int) ($driver['trips'] ?? 0));
            $ws->setCellValue("E{$r}", (float) ($driver['hours'] ?? 0));
            $ws->setCellValue("F{$r}", (int) ($driver['freqScore'] ?? 0));
            $ws->setCellValue("G{$r}", (int) ($driver['diversityScore'] ?? 0));
            $ws->setCellValue("H{$r}", $driver['bonusLabel'] ?? '');
            $ws->setCellValue("I{$r}", $driver['bonusNote'] ?? '');

            $this->styleDataRow($ws, "A{$r}:I{$r}", $i % 2 === 1);
            $ws->getStyle("A{$r}:C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle("D{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $ws->getStyle("E{$r}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
            $ws->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $totalRow = $dataStart + count($drivers);
        if ($drivers !== []) {
            $sumTrips = array_sum(array_column($drivers, 'trips'));
            $sumHours = array_sum(array_map(fn ($d) => (float) ($d['hours'] ?? 0), $drivers));
            $ws->setCellValue("A{$totalRow}", 'TỔNG / TB');
            $ws->setCellValue("D{$totalRow}", $sumTrips);
            $ws->setCellValue("E{$totalRow}", round($sumHours, 1));
            $this->applyStyle($ws, "A{$totalRow}:I{$totalRow}", [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
            ]);
            $ws->getStyle("D{$totalRow}:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $ws->getStyle("E{$totalRow}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
            $this->applyBorders($ws, 'A'.$headerRow.':I'.$totalRow);
            $this->writeSignatureBlock($ws, $totalRow + 2, 'A', 'I');
        }

        $ws->freezePane('A'.($headerRow + 1));
    }

    /**
     * @return int Header row index
     */
    private function writeSheetHeader(
        Worksheet $ws,
        string $title,
        array $meta,
        string $lastCol,
        bool $withApproval = false,
    ): int {
        $ws->getRowDimension(1)->setRowHeight(6);

        $ws->mergeCells("C2:{$lastCol}2");
        $ws->setCellValue('C2', $title);
        $this->applyStyle($ws, "C2:{$lastCol}2", [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::VA_RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ws->mergeCells("C3:{$lastCol}3");
        $ws->setCellValue('C3', 'Hệ Thống Điều Vận Nội Bộ  ·  Vietnam America Schools');
        $this->applyStyle($ws, "C3:{$lastCol}3", [
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $ws->mergeCells('A5:D5');
        $ws->setCellValue('A5', $meta['periodLine'].'   |   Ngày xuất: '.$meta['exportDate']);
        $this->applyStyle($ws, 'A5:D5', ['font' => ['size' => 8, 'color' => ['argb' => 'FF444444']]]);

        if ($withApproval) {
            $ws->mergeCells('E5:G5');
            $ws->setCellValue('E5', 'Người xuất:  '.$meta['exportedBy']);
            $ws->mergeCells("J5:{$lastCol}5");
            $ws->setCellValue('J5', 'Phê duyệt:  '.$meta['approvalBlank']);
        } else {
            $ws->mergeCells("E5:{$lastCol}5");
            $ws->setCellValue('E5', 'Người xuất:  '.$meta['exportedBy']);
        }
        $this->applyStyle($ws, "E5:{$lastCol}5", ['font' => ['size' => 8, 'color' => ['argb' => 'FF444444']]]);

        $ws->getRowDimension(5)->setRowHeight(16);
        $ws->mergeCells("A6:{$lastCol}6");
        $ws->getStyle("A6:{$lastCol}6")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::VA_RED);
        $ws->getRowDimension(6)->setRowHeight(4);

        return 7;
    }

    /** @param  array<string, string>  $headers */
    private function writeTableHeader(Worksheet $ws, int $row, array $headers, string $firstCol, string $lastCol): void
    {
        $ws->getRowDimension($row)->setRowHeight(20);
        foreach ($headers as $col => $label) {
            $ws->setCellValue($col.$row, $label);
        }
        $this->applyStyle($ws, $firstCol.$row.':'.$lastCol.$row, [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF555577']]],
        ]);
    }

    private function styleDataRow(Worksheet $ws, string $range, bool $zebra): void
    {
        $bg = $zebra ? self::ZEBRA_BG : 'FFFFFFFF';
        $this->applyStyle($ws, $range, [
            'font' => ['size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFD0D0D0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    /** @param  list<array<string, mixed>>  $drivers */
    private function writeDriverTotalsRow(Worksheet $ws, int $row, array $drivers, string $firstCol, string $lastCol): void
    {
        $sumTrips = array_sum(array_column($drivers, 'trips'));
        $sumHours = array_sum(array_map(fn ($d) => (float) ($d['hours'] ?? 0), $drivers));
        $sumAvg = array_sum(array_map(fn ($d) => (float) ($d['avgTripsPerMonth'] ?? 0), $drivers));
        $typeSums = [0, 0, 0, 0];
        foreach ($drivers as $d) {
            foreach ($d['typesExport'] ?? [] as $i => $c) {
                $typeSums[$i] = ($typeSums[$i] ?? 0) + (int) $c;
            }
        }

        $ws->setCellValue("A{$row}", 'TỔNG / TB');
        $ws->setCellValue("D{$row}", $sumTrips);
        $ws->setCellValue("E{$row}", round($sumHours, 1));
        $ws->setCellValue("F{$row}", count($drivers) > 0 ? round($sumAvg / count($drivers), 1) : 0);
        $ws->setCellValue("G{$row}", $typeSums[0] ?? 0);
        $ws->setCellValue("H{$row}", $typeSums[1] ?? 0);
        $ws->setCellValue("I{$row}", $typeSums[2] ?? 0);
        $ws->setCellValue("J{$row}", $typeSums[3] ?? 0);

        $this->applyStyle($ws, "{$firstCol}{$row}:{$lastCol}{$row}", [
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
        ]);
        $ws->getStyle("D{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
    }

    /** @param  list<array<string, mixed>>  $vehicles */
    private function writeVehicleTotalsRow(Worksheet $ws, int $row, array $vehicles): void
    {
        $sumTrips = array_sum(array_column($vehicles, 'trips'));
        $sumKm = array_sum(array_map(fn ($v) => (int) ($v['totalKm'] ?? 0), $vehicles));
        $sumHours = array_sum(array_map(fn ($v) => (float) ($v['hours'] ?? 0), $vehicles));
        $sumAvg = array_sum(array_map(fn ($v) => (float) ($v['avgTripsPerMonth'] ?? 0), $vehicles));

        $ws->setCellValue("A{$row}", 'TỔNG');
        $ws->setCellValue("E{$row}", $sumTrips);
        $ws->setCellValue("F{$row}", $sumKm);
        $ws->setCellValue("G{$row}", round($sumHours, 1));
        $ws->setCellValue("H{$row}", count($vehicles) > 0 ? round($sumAvg / count($vehicles), 1) : 0);

        $this->applyStyle($ws, "A{$row}:J{$row}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
        ]);
        $ws->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode(self::DEC_FMT);
        $ws->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::NUM_FMT);
    }

    private function writeSignatureBlock(Worksheet $ws, int $startRow, string $firstCol, string $lastCol): void
    {
        $mid = $this->columnLetter((int) floor(($this->columnIndex($lastCol) + $this->columnIndex($firstCol)) / 2));

        $sigHeader = $startRow;
        $ws->getRowDimension($sigHeader)->setRowHeight(18);
        $ws->mergeCells("{$firstCol}{$sigHeader}:{$mid}{$sigHeader}");
        $ws->mergeCells($this->advanceCol($mid, 1).$sigHeader.":{$lastCol}{$sigHeader}");
        $ws->setCellValue("{$firstCol}{$sigHeader}", 'Người lập');
        $ws->setCellValue($this->advanceCol($mid, 1).$sigHeader, 'Phê duyệt');
        $this->applyStyle($ws, "{$firstCol}{$sigHeader}:{$lastCol}{$sigHeader}", [
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SIG_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
        ]);

        foreach (range($sigHeader + 1, $sigHeader + 3) as $r) {
            $ws->getRowDimension($r)->setRowHeight(26);
            $ws->mergeCells("{$firstCol}{$r}:{$mid}{$r}");
            $ws->mergeCells($this->advanceCol($mid, 1).$r.":{$lastCol}{$r}");
            $this->applyStyle($ws, "{$firstCol}{$r}:{$lastCol}{$r}", [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
        }

        $labelRow = $sigHeader + 4;
        $ws->mergeCells("{$firstCol}{$labelRow}:{$mid}{$labelRow}");
        $ws->mergeCells($this->advanceCol($mid, 1).$labelRow.":{$lastCol}{$labelRow}");
        $ws->setCellValue("{$firstCol}{$labelRow}", '(Ký và ghi rõ họ tên)');
        $ws->setCellValue($this->advanceCol($mid, 1).$labelRow, '(Ký và ghi rõ họ tên)');
        $this->applyStyle($ws, "{$firstCol}{$labelRow}:{$lastCol}{$labelRow}", [
            'font' => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF888888']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function applyBorders(Worksheet $ws, string $range): void
    {
        $this->applyStyle($ws, $range, [
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF888888']]],
        ]);
    }

    /** @param  array<string, float|int>  $widths */
    private function setColumnWidths(Worksheet $ws, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $ws->getColumnDimension($col)->setWidth((float) $w);
        }
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function columnIndex(string $col): int
    {
        $col = strtoupper($col);
        $n = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }

        return $n;
    }

    private function advanceCol(string $col, int $steps): string
    {
        return $this->columnLetter($this->columnIndex($col) + $steps);
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
            isset($al['wrapText']) && $style->getAlignment()->setWrapText($al['wrapText']);
        }

        if (isset($styleArr['borders'])) {
            $style->applyFromArray(['borders' => $styleArr['borders']]);
        }
    }
}
