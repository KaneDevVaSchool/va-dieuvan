<?php

namespace App\Services\Reports\Export;

use App\Services\Reports\TripCostReportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Xuất XLSX báo cáo chi phí chuyến theo mẫu ChiPhiChuyen_Final.xlsx:
 *  - Sheet 1: tất cả dòng
 *  - Sheet 2: chỉ chuyến công tác (trip_type = business)
 *  - Header VA brand, thông tin xuất, bảng chi tiết, TỔNG CỘNG, ô ký tên
 */
class TripCostReportXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const VA_LIGHT = 'FFFDF2F5';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    private const TOTAL_BG = 'FFF5F5F5';

    private const SIG_BG = 'FFEDEDED';

    private const MONEY_FMT = '#,##0';

    private const COLS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

    /**
     * Ghi toàn bộ workbook vào file tạm, trả về đường dẫn file tạm.
     *
     * @param  array<int, array<string, mixed>>  $allRows  Tất cả rows (từ TripCostReportService::rows)
     * @param  array{from?:string, to?:string}  $filters
     * @param  string|null  $exportedBy  Tên người xuất (tuỳ chọn)
     * @param  string|null  $unitName  Tên đơn vị (tuỳ chọn)
     */
    public function writeTempFile(
        array $allRows,
        array $filters = [],
        ?string $exportedBy = null,
        ?string $unitName = null,
    ): string {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Báo cáo doanh thu chuyến')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $businessRows = array_values(array_filter(
            $allRows,
            fn ($r) => ($r['category'] ?? '') === 'business',
        ));

        // Sheet 1 — all rows
        $ws1 = $spreadsheet->getActiveSheet();
        $ws1->setTitle('Danh sách doanh thu');
        $this->buildSheet($ws1, $allRows, 'BÁO CÁO CHI PHÍ CHUYẾN', $filters, $exportedBy, $unitName);

        // Sheet 2 — business only
        $ws2 = $spreadsheet->createSheet();
        $ws2->setTitle('Doanh thu công tác');
        $this->buildSheet($ws2, $businessRows, 'CHI PHÍ CÔNG TÁC', $filters, $exportedBy, $unitName);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'cost_rpt_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    // ──────────────────────────────────────────────────────────────────────────

    /** @param  array<int, array<string, mixed>>  $rows */
    private function buildSheet(
        Worksheet $ws,
        array $rows,
        string $title,
        array $filters,
        ?string $exportedBy,
        ?string $unitName,
    ): void {
        // ── Column widths ──
        $ws->getColumnDimension('A')->setWidth(6);
        $ws->getColumnDimension('B')->setWidth(16);
        $ws->getColumnDimension('C')->setWidth(14);
        $ws->getColumnDimension('D')->setWidth(18);
        $ws->getColumnDimension('E')->setWidth(42);
        $ws->getColumnDimension('F')->setWidth(22);
        $ws->getColumnDimension('G')->setWidth(20);
        $ws->getColumnDimension('H')->setWidth(14);
        $ws->getColumnDimension('I')->setWidth(14);
        $ws->getColumnDimension('J')->setWidth(14);
        $ws->getColumnDimension('K')->setWidth(16);

        // ── ROW 1: empty spacer ──
        $ws->getRowDimension(1)->setRowHeight(6);

        // ── ROW 2: main title ──
        $ws->getRowDimension(2)->setRowHeight(24);
        $ws->mergeCells('C2:K2');
        $ws->setCellValue('C2', $title);
        $this->applyStyle($ws, 'C2:K2', [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::VA_RED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── ROW 3: subtitle ──
        $ws->getRowDimension(3)->setRowHeight(16);
        $ws->mergeCells('C3:K3');
        $ws->setCellValue('C3', 'Hệ Thống Điều Vận Nội Bộ  ·  Vietnam America Schools');
        $this->applyStyle($ws, 'C3:K3', [
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── ROW 4: export meta ──
        $ws->getRowDimension(4)->setRowHeight(15);
        $exportDate = now()->format('d/m/Y');
        $fromLabel = ! empty($filters['from']) ? date('d/m/Y', strtotime($filters['from'])) : '—';
        $toLabel = ! empty($filters['to']) ? date('d/m/Y', strtotime($filters['to'])) : '—';
        $dateRange = ($filters['from'] ?? null) || ($filters['to'] ?? null)
            ? "Kỳ: {$fromLabel} → {$toLabel}"
            : '';

        $ws->mergeCells('A4:C4');
        $ws->setCellValue('A4', "Ngày xuất: {$exportDate}".($dateRange ? "   {$dateRange}" : ''));
        $this->applyStyle($ws, 'A4:C4', ['font' => ['size' => 8, 'color' => ['argb' => 'FF444444']]]);

        $ws->mergeCells('D4:G4');
        $ws->setCellValue('D4', 'Người xuất:  '.($exportedBy ?? '_______________________________'));
        $this->applyStyle($ws, 'D4:G4', ['font' => ['size' => 8, 'color' => ['argb' => 'FF444444']]]);

        $ws->mergeCells('H4:K4');
        $ws->setCellValue('H4', 'Đơn vị:  '.($unitName ?? '_______________________________'));
        $this->applyStyle($ws, 'H4:K4', ['font' => ['size' => 8, 'color' => ['argb' => 'FF444444']]]);

        // ── ROW 5: thin separator ──
        $ws->getRowDimension(5)->setRowHeight(4);
        $ws->mergeCells('A5:K5');
        $ws->getStyle('A5:K5')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::VA_RED);

        // ── ROW 6: column headers ──
        $headerRow = 6;
        $ws->getRowDimension($headerRow)->setRowHeight(20);
        $headers = [
            'A' => 'STT',
            'B' => 'ĐƠN VỊ',
            'C' => 'PHÂN LOẠI',
            'D' => 'NGƯỜI ĐỀ XUẤT',
            'E' => 'NỘI DUNG',
            'F' => 'NGUỒN LỰC CHUYẾN',
            'G' => 'NHÀ CUNG CẤP',
            'H' => 'ĐƠN GIÁ',
            'I' => 'PHỤ THU',
            'J' => 'THÀNH TIỀN',
            'K' => 'TRẠNG THÁI',
        ];
        foreach ($headers as $col => $label) {
            $ws->setCellValue("{$col}{$headerRow}", $label);
        }
        $this->applyStyle($ws, "A{$headerRow}:K{$headerRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF555577']]],
        ]);
        // Right-align money headers
        foreach (['H', 'I', 'J'] as $col) {
            $ws->getStyle("{$col}{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $ws->freezePane("A{$headerRow}");

        // ── Data rows ──
        $dataStartRow = $headerRow + 1;
        $n = count($rows);

        foreach ($rows as $i => $row) {
            $r = $dataStartRow + $i;
            $even = ($i % 2 === 1);
            $ws->getRowDimension($r)->setRowHeight(16);

            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $row['unit'] ?? '—');
            $ws->setCellValue("C{$r}", TripCostReportService::TRIP_TYPE_LABELS[$row['category'] ?? ''] ?? ($row['category'] ?? '—'));
            $ws->setCellValue("D{$r}", $row['submitter'] ?? '—');
            $legLabel = trim((string) ($row['leg_label'] ?? ''));
            $descCell = (string) ($row['description'] ?? '—');
            $ws->setCellValue("E{$r}", $legLabel !== '' ? "[{$legLabel}] {$descCell}" : $descCell);
            $ws->setCellValue("F{$r}", $row['fleet_source'] ?? '—');
            $ws->setCellValue("G{$r}", $row['provider'] ?: '—');

            $up = $row['unit_price'];
            $ef = $row['extra_fee'];
            $amt = (float) ($row['amount'] ?? 0);

            $ws->setCellValue("H{$r}", $up !== null ? (float) $up : '');
            $ws->setCellValue("I{$r}", $ef !== null ? (float) $ef : '');
            $ws->setCellValue("J{$r}", $amt);
            $ws->setCellValue("K{$r}", $row['status_label'] ?? ($row['status'] ?? '—'));

            // Formats for money cells
            foreach (['H', 'I', 'J'] as $col) {
                $ws->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode(self::MONEY_FMT);
                $ws->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $rowBg = $even ? self::ZEBRA_BG : 'FFFFFFFF';
            $this->applyStyle($ws, "A{$r}:K{$r}", [
                'font' => ['size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $ws->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ── TỔNG CỘNG row ──
        $totalRow = $dataStartRow + $n;
        $ws->getRowDimension($totalRow)->setRowHeight(18);
        $ws->mergeCells("A{$totalRow}:G{$totalRow}");
        $ws->setCellValue("A{$totalRow}", 'TỔNG CỘNG');

        $sumUp = (float) array_sum(array_map(
            fn ($r) => $r['unit_price'] !== null ? (float) $r['unit_price'] : 0,
            $rows,
        ));
        $sumEf = (float) array_sum(array_map(
            fn ($r) => $r['extra_fee'] !== null ? (float) $r['extra_fee'] : 0,
            $rows,
        ));
        $sumAmt = (float) array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $rows));

        $ws->setCellValue("H{$totalRow}", $sumUp > 0 ? $sumUp : '');
        $ws->setCellValue("I{$totalRow}", $sumEf > 0 ? $sumEf : '');
        $ws->setCellValue("J{$totalRow}", $sumAmt);

        foreach (['H', 'I', 'J'] as $col) {
            $ws->getStyle("{$col}{$totalRow}")->getNumberFormat()->setFormatCode(self::MONEY_FMT);
            $ws->getStyle("{$col}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $this->applyStyle($ws, "A{$totalRow}:K{$totalRow}", [
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF888888']],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFB0B0B0']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Spacer row ──
        $spaceRow = $totalRow + 1;
        $ws->getRowDimension($spaceRow)->setRowHeight(10);
        $ws->mergeCells("A{$spaceRow}:K{$spaceRow}");

        // ── Signature header row ──
        $sigHeaderRow = $spaceRow + 2;
        $ws->getRowDimension($sigHeaderRow)->setRowHeight(18);
        $ws->mergeCells("A{$sigHeaderRow}:D{$sigHeaderRow}");
        $ws->mergeCells("E{$sigHeaderRow}:G{$sigHeaderRow}");
        $ws->mergeCells("H{$sigHeaderRow}:K{$sigHeaderRow}");

        foreach ([
            "A{$sigHeaderRow}" => 'Người lập',
            "E{$sigHeaderRow}" => 'Kế toán xác nhận',
            "H{$sigHeaderRow}" => 'Phê duyệt',
        ] as $cell => $label) {
            $ws->setCellValue($cell, $label);
        }

        $this->applyStyle($ws, "A{$sigHeaderRow}:K{$sigHeaderRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF333333']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SIG_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
        ]);

        // ── Signature space rows ──
        foreach (range($sigHeaderRow + 1, $sigHeaderRow + 3) as $emptyR) {
            $ws->getRowDimension($emptyR)->setRowHeight(28);
            $ws->mergeCells("A{$emptyR}:D{$emptyR}");
            $ws->mergeCells("E{$emptyR}:G{$emptyR}");
            $ws->mergeCells("H{$emptyR}:K{$emptyR}");
            $this->applyStyle($ws, "A{$emptyR}:K{$emptyR}", [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            ]);
        }

        // ── (Ký và ghi rõ họ tên) row ──
        $signLabelRow = $sigHeaderRow + 4;
        $ws->getRowDimension($signLabelRow)->setRowHeight(14);
        $ws->mergeCells("A{$signLabelRow}:D{$signLabelRow}");
        $ws->mergeCells("E{$signLabelRow}:G{$signLabelRow}");
        $ws->mergeCells("H{$signLabelRow}:K{$signLabelRow}");

        foreach ([
            "A{$signLabelRow}",
            "E{$signLabelRow}",
            "H{$signLabelRow}",
        ] as $cell) {
            $ws->setCellValue($cell, '(Ký và ghi rõ họ tên)');
        }

        $this->applyStyle($ws, "A{$signLabelRow}:K{$signLabelRow}", [
            'font' => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF888888']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottomBorder' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────

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
