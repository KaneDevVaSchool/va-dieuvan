<?php

namespace App\Services\DispatchRequest;

use Carbon\Carbon;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Điền mẫu Excel BM.02/MH.QT.04 (P2P) từ wizard_snapshot.
 * PDF: mPDF từ HTML mẫu Google (nội bộ) — không gửi HTML ra API; không dùng PhpSpreadsheet→HTML→PDF.
 * Excel: ô tick bằng ảnh PNG (Drawing) để hiển thị ổn định trên mọi Excel.
 */
class Bm02P2pFormGenerator
{
    /** Ký tự dự phòng khi không có file ảnh tick. */
    private const CHECKED_MARK = "\u{2611}";

    /** Cùng thứ tự với `targetOptions` trong DispatchRequestCreateView.vue — ô tick tương ứng (null = không có checkbox). */
    private const P2P_TARGET_CHECKBOX_CELLS = [
        'B25', null, 'H25', 'K25',
        'B26', null, 'H26', 'K26',
        'B27', null, 'H27', 'K27',
        'B28', null, 'H28', 'K28',
        'B29', null, 'H29', 'K29',
        'B30', null, 'H30', 'K30',
        'B31', null, 'H31', 'K31',
        'B32', null, 'H32',
    ];

    /** @var array<int, string> */
    private const TARGET_LABELS = [
        'TiH Tân Bình',
        'MN Phú Định',
        'P. Kinh Doanh',
        'Vườn Trường',
        'THCS Tân Bình',
        'TiH-THCS Phú Định',
        'P. Công Nghệ',
        'Ban TA TiHo',
        'MN Bình Thới',
        'MN Vĩnh Hội',
        'BP.CUHC',
        'Ban TA THCS',
        'TiH Bình Thới',
        'MN Thông Tây Hội',
        'P. Kế Toán',
        'Ban TA THPT',
        'THCS Bình Thới',
        'TiH-THCS Thông Tây Hội',
        'P. Mua Hàng',
        'VA - Cần Thơ',
        'THPT VMA',
        'MN Hạnh Thông',
        'P. Đầu Tư',
        'VA - Vũng Tàu',
        'MN Hòa Bình',
        'P.CSVC',
        'Khóa Hè',
        'Viễn Đông',
        'P.HCNS',
        'Tham vấn học đường',
        'Ban Pháp chế (P.CSVC)',
    ];

    /** Ô boolean trên mẫu gốc nhưng không map vào `targetOptions`. */
    private const P2P_EXTRA_TEMPLATE_BOOL_CELLS = [
        'K32',
    ];

    /**
     * @param  array{form?: array, passengerRows?: array<int, array>, businessRows?: array<int, array>, cargoRows?: array<int, array>}  $wizard
     * @return array{xlsx: string, pdf: string, excel_checkbox_cells: array<string, bool>}
     */
    public function generate(array $wizard): array
    {
        $vm = $this->buildViewModel($wizard);

        $path = $this->templatePath();
        $reader = IOFactory::createReader('Xlsx');
        /** @var Spreadsheet $ss */
        $ss = $reader->load($path);
        $sheet = $ss->getActiveSheet();
        $sheet->getDrawingCollection()->exchangeArray([]);

        $ss->getProperties()
            ->setTitle('Đề nghị điều vận BM.02')
            ->setSubject('BM.02/MH.QT.04');

        $this->fillSpreadsheetFromViewModel($sheet, $vm);

        $writerXlsx = IOFactory::createWriter($ss, 'Xlsx');
        ob_start();
        $writerXlsx->save('php://output');
        $xlsxBinary = ob_get_clean();

        $html = $this->renderPrintHtml($vm);
        $pdfBinary = $this->renderPdfFromHtml($html);

        return [
            'xlsx' => $xlsxBinary,
            'pdf' => $pdfBinary,
            'excel_checkbox_cells' => $this->buildExcelCheckboxCellStates($vm),
        ];
    }

    /**
     * @param  array<string, mixed>  $vm
     * @return array<string, bool>
     */
    private function buildExcelCheckboxCellStates(array $vm): array
    {
        $out = ['B21' => ! empty($vm['is_urgent'])];
        $tickSet = [];
        foreach ($vm['target_tick_cells'] ?? [] as $c) {
            if (is_string($c) && $c !== '') {
                $tickSet[strtoupper($c)] = true;
            }
        }
        foreach (self::P2P_TARGET_CHECKBOX_CELLS as $coord) {
            if ($coord === null || $coord === '') {
                continue;
            }
            $u = strtoupper((string) $coord);
            $out[$u] = isset($tickSet[$u]);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillSpreadsheetFromViewModel(Worksheet $sheet, array $vm): void
    {
        $this->resetTemplateCheckboxCells($sheet);

        $sheet->setCellValue('N3', ExcelDate::PHPToExcel($vm['now_excel']));
        $sheet->getStyle('N3')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        $sheet->setCellValue('E7', $vm['requester_name']);
        $sheet->setCellValue('E8', $vm['requester_email']);
        $sheet->setCellValue('E9', $vm['requester_phone']);
        $sheet->setCellValue('E10', $vm['requester_unit']);

        $sheet->setCellValue('C13', $vm['purpose']);
        $sheet->setCellValue('C14', $vm['basis_note']);

        $sheet->setCellValue('E17', $vm['proposed_date']);
        $sheet->setCellValue('E20', $vm['date_needed']);

        $sheet->getCell('B21')->setValueExplicit('', DataType::TYPE_STRING);
        $sheet->setCellValue('E21', $vm['is_urgent'] ? $vm['urgent_reason'] : '');

        $this->applyCheckboxGraphics($sheet, $vm);

        if ($vm['coordinator_line'] !== '') {
            $sheet->setCellValue('E33', $vm['coordinator_line']);
        }

        foreach ($vm['excel_passenger_rows'] as $i => $r) {
            $excelRow = 40 + $i;
            if (! is_array($r)) {
                continue;
            }
            $sheet->setCellValue("C{$excelRow}", $r['depart_at'] ?? '');
            $sheet->setCellValue("D{$excelRow}", $r['pickup'] ?? '');
            $sheet->setCellValue("E{$excelRow}", $r['return_at'] ?? '');
            $sheet->setCellValue("G{$excelRow}", $r['dropoff'] ?? '');
            $sheet->setCellValue("I{$excelRow}", $r['guests'] ?? '');
            $sheet->setCellValue("J{$excelRow}", $r['person_in_charge'] ?? '');
            $sheet->setCellValue("K{$excelRow}", $r['unit_price'] ?? '');
            $sheet->setCellValue("L{$excelRow}", $r['extra_fee'] ?? '');
            $sheet->setCellValue("M{$excelRow}", $r['line_total'] ?? '');
            $sheet->setCellValue("N{$excelRow}", $r['notes'] ?? '');
        }

        if ($vm['passenger_total_raw'] > 0) {
            $sheet->setCellValue('L45', $vm['passenger_total_raw']);
            $sheet->getStyle('L45')->getNumberFormat()->setFormatCode('#,##0');
        }

        if ($vm['requester_name'] !== '') {
            $sheet->setCellValue('E55', $vm['requester_name']);
        }
        $sheet->setCellValue('D55', ' ');
        $sheet->setCellValue('I55', ' ');
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function applyCheckboxGraphics(Worksheet $sheet, array $vm): void
    {
        $png = $this->checkboxTickImagePath();
        $useImage = is_readable($png);

        if ($vm['is_urgent']) {
            if ($useImage) {
                $this->placeTickDrawing($sheet, $png, 'B21');
            } else {
                $sheet->getCell('B21')->setValueExplicit(self::CHECKED_MARK, DataType::TYPE_STRING);
            }
        }

        foreach ($vm['target_tick_cells'] as $coord) {
            if ($useImage) {
                $this->placeTickDrawing($sheet, $png, $coord);
            } else {
                $sheet->getCell($coord)->setValueExplicit(self::CHECKED_MARK, DataType::TYPE_STRING);
            }
        }
    }

    private function placeTickDrawing(Worksheet $sheet, string $pngPath, string $coord): void
    {
        $drawing = new Drawing;
        $drawing->setName('tick');
        $drawing->setDescription('Đã chọn');
        $drawing->setPath($pngPath);
        $drawing->setCoordinates($coord);
        $drawing->setWidth(12);
        $drawing->setHeight(12);
        $drawing->setOffsetX(2);
        $drawing->setOffsetY(1);
        $drawing->setWorksheet($sheet);
    }

    private function checkboxTickImagePath(): string
    {
        return dirname(__DIR__, 3).'/resources/dispatch/bm02_checkbox_tick.png';
    }

    /**
     * @param  array{form?: array, passengerRows?: array<int, array>}  $wizard
     * @return array<string, mixed>
     */
    private function buildViewModel(array $wizard): array
    {
        $form = $wizard['form'] ?? [];
        $rows = $wizard['passengerRows'] ?? [];
        $targets = $form['targets'] ?? [];

        $filled = array_values(array_filter(
            is_array($rows) ? $rows : [],
            fn ($r) => is_array($r) && $this->rowHasPassengerContent($r),
        ));

        $selectedNorm = [];
        foreach (is_array($targets) ? $targets : [] as $s) {
            $key = $this->normalizeTargetLabel((string) $s);
            if ($key !== '') {
                $selectedNorm[$key] = true;
            }
        }

        $targetTickCells = [];
        foreach (self::TARGET_LABELS as $i => $label) {
            $cell = self::P2P_TARGET_CHECKBOX_CELLS[$i] ?? null;
            if ($cell === null || $cell === '') {
                continue;
            }
            if (! empty($selectedNorm[$this->normalizeTargetLabel($label)])) {
                $targetTickCells[] = $cell;
            }
        }

        $basisNote = '';
        if (! empty($form['basisFileName'])) {
            $basisNote = 'Đính kèm: '.$form['basisFileName'];
        }
        $maxPassengerLinesOnForm = 5;
        $overflowNote = null;
        if (count($filled) > $maxPassengerLinesOnForm) {
            $n = count($filled);
            $overflowNote = "(Ghi chú: {$n} chuyến đã khai báo — trên mẫu in hiển thị tối đa {$maxPassengerLinesOnForm} dòng đầu; toàn bộ nằm trong portal.)";
            $basisNote = $basisNote !== '' ? $basisNote."\n\n".$overflowNote : $overflowNote;
        }

        $coord = [];
        if (! empty($form['coordinator_name'])) {
            $coord[] = (string) $form['coordinator_name'];
        }
        if (! empty($form['coordinator_email'])) {
            $coord[] = (string) $form['coordinator_email'];
        }
        if (! empty($form['coordinator_phone'])) {
            $coord[] = (string) $form['coordinator_phone'];
        }
        $coordinatorLine = $coord !== [] ? implode(' — ', $coord) : '';

        $now = Carbon::now();
        $isUrgent = ! empty($form['is_urgent']);

        $slots = [];
        foreach (self::TARGET_LABELS as $i => $lab) {
            $coordCell = self::P2P_TARGET_CHECKBOX_CELLS[$i] ?? null;
            $checked = $coordCell && ! empty($selectedNorm[$this->normalizeTargetLabel($lab)]);
            $slots[] = ['label' => $lab, 'checked' => $checked];
        }
        while (count($slots) % 4 !== 0) {
            $slots[] = null;
        }
        /** @var array<int, array<int, array<string, mixed>|null>> $targetTableRows */
        $targetTableRows = array_chunk($slots, 4);

        $excelPassengerRows = [];
        $tripLines = [];
        for ($i = 0; $i < $maxPassengerLinesOnForm; $i++) {
            $r = $filled[$i] ?? null;
            $er = $this->excelRowFromPassenger(is_array($r) ? $r : null);
            $excelPassengerRows[] = $er;
            $tripLines[] = [
                'depart_at' => (string) ($er['depart_at'] ?? ''),
                'pickup' => (string) ($er['pickup'] ?? ''),
                'return_at' => (string) ($er['return_at'] ?? ''),
                'dropoff' => (string) ($er['dropoff'] ?? ''),
                'guests' => $er['guests'] === '' ? '' : (string) $er['guests'],
                'person_in_charge' => (string) ($er['person_in_charge'] ?? ''),
                'unit_price' => $er['unit_price'] === '' ? '' : (string) $er['unit_price'],
                'extra_fee' => $er['extra_fee'] === '' ? '' : (string) $er['extra_fee'],
                'line_total' => $er['line_total'] === '' ? '' : (string) $er['line_total'],
                'notes' => (string) ($er['notes'] ?? ''),
            ];
        }

        $total = $this->passengerRowsTotal($filled);

        return [
            'now_excel' => $now,
            'printed_at' => $now->format('d/m/Y H:i'),
            'issued_date' => $now->format('d/m/Y'),
            'google_signatory_line2' => (string) ($form['coordinator_name'] ?? ''),
            'google_checkbox_states' => $this->buildGoogleCheckboxStates($selectedNorm, $isUrgent),
            'requester_name' => (string) ($form['requester_name'] ?? ''),
            'requester_email' => (string) ($form['requester_email'] ?? ''),
            'requester_phone' => (string) ($form['requester_phone'] ?? ''),
            'requester_unit' => (string) ($form['requester_unit'] ?? ''),
            'purpose' => (string) ($form['purpose'] ?? ''),
            'basis_note' => $basisNote,
            'proposed_date' => $this->formatDateCell($form['proposed_date'] ?? ''),
            'date_needed' => $this->formatDateCell($form['date_needed'] ?? ''),
            'is_urgent' => $isUrgent,
            'urgent_reason' => (string) ($form['urgent_reason'] ?? ''),
            'coordinator_line' => $coordinatorLine,
            'target_tick_cells' => $targetTickCells,
            'target_table_rows' => $targetTableRows,
            'excel_passenger_rows' => $excelPassengerRows,
            'trip_lines' => $tripLines,
            'max_trip_lines' => $maxPassengerLinesOnForm,
            'passenger_total_raw' => $total,
            'passenger_total' => $total > 0 ? number_format($total, 0, ',', '.') : '',
            'overflow_note' => $overflowNote,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function excelRowFromPassenger(?array $r): array
    {
        if (! is_array($r)) {
            return [
                'depart_at' => '',
                'pickup' => '',
                'return_at' => '',
                'dropoff' => '',
                'guests' => '',
                'person_in_charge' => '',
                'unit_price' => '',
                'extra_fee' => '',
                'line_total' => '',
                'notes' => '',
            ];
        }

        $unit = (float) ($r['unit_price'] ?? 0);
        $extra = (float) ($r['extra_fee'] ?? 0);
        $lineTotal = $unit + $extra > 0 ? $unit + $extra : '';

        return [
            'depart_at' => $this->formatDateTimeCell($r['depart_at'] ?? ''),
            'pickup' => (string) ($r['pickup'] ?? ''),
            'return_at' => $this->formatDateTimeCell($r['return_at'] ?? ''),
            'dropoff' => (string) ($r['dropoff'] ?? ''),
            'guests' => $this->numOrEmpty($r['guests'] ?? ''),
            'person_in_charge' => (string) ($r['person_in_charge'] ?? ''),
            'unit_price' => $this->numOrEmpty($r['unit_price'] ?? ''),
            'extra_fee' => $this->numOrEmpty($r['extra_fee'] ?? ''),
            'line_total' => $lineTotal,
            'notes' => (string) ($r['notes'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function renderPrintHtml(array $vm): string
    {
        $google = new Bm02P2pGoogleSheetHtmlRenderer;
        $html = $google->render($vm);
        if ($html !== null) {
            return $html;
        }

        return view('dispatch.bm02-p2p-print', ['vm' => $vm])->render();
    }

    /**
     * @param  array<string, true>  $selectedNorm
     * @return list<bool>
     */
    private function buildGoogleCheckboxStates(array $selectedNorm, bool $isUrgent): array
    {
        $states = [$isUrgent];
        foreach (self::TARGET_LABELS as $i => $label) {
            $cell = self::P2P_TARGET_CHECKBOX_CELLS[$i] ?? null;
            if ($cell === null || $cell === '') {
                continue;
            }
            $states[] = ! empty($selectedNorm[$this->normalizeTargetLabel($label)]);
        }

        return $states;
    }

    private function renderPdfFromHtml(string $html): string
    {
        $tempDir = storage_path('app/mpdf-tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $tempDir,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->SetTitle('BM.02 / MH.QT.04');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    private function resetTemplateCheckboxCells(Worksheet $sheet): void
    {
        $sheet->getCell('B21')->setValueExplicit('', DataType::TYPE_STRING);
        foreach (self::P2P_TARGET_CHECKBOX_CELLS as $coord) {
            if ($coord === null || $coord === '') {
                continue;
            }
            $sheet->getCell($coord)->setValueExplicit('', DataType::TYPE_STRING);
        }
        foreach (self::P2P_EXTRA_TEMPLATE_BOOL_CELLS as $coord) {
            $sheet->getCell($coord)->setValueExplicit('', DataType::TYPE_STRING);
        }
    }

    private function normalizeTargetLabel(string $s): string
    {
        $s = str_replace('_', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return mb_strtolower(trim($s), 'UTF-8');
    }

    private function templatePath(): string
    {
        $root = dirname(__DIR__, 3);
        $scripts = $root.'/scripts/P2P.xlsx';
        if (is_readable($scripts)) {
            return $scripts;
        }
        $storage = $root.'/storage/app/templates/P2P.xlsx';
        if (is_readable($storage)) {
            return $storage;
        }

        throw new \RuntimeException('Không tìm thấy mẫu P2P.xlsx (scripts/P2P.xlsx hoặc storage/app/templates/P2P.xlsx).');
    }

    private function formatDateCell(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        try {
            return Carbon::parse((string) $v)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    private function formatDateTimeCell(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        try {
            return Carbon::parse((string) $v)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    private function numOrEmpty(mixed $v): string|float
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $n = (float) preg_replace('/[^\d.-]/', '', (string) $v);

        return $n !== 0.0 ? $n : '';
    }

    private function rowHasPassengerContent(array $r): bool
    {
        if (trim((string) ($r['pickup'] ?? '')) !== '' || trim((string) ($r['dropoff'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($r['depart_at'] ?? '')) !== '' || trim((string) ($r['return_at'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($r['person_in_charge'] ?? '')) !== '' || trim((string) ($r['notes'] ?? '')) !== '') {
            return true;
        }
        if (isset($r['unit_price']) && trim((string) $r['unit_price']) !== '') {
            return true;
        }
        if (isset($r['extra_fee']) && trim((string) $r['extra_fee']) !== '') {
            return true;
        }
        $g = trim((string) ($r['guests'] ?? '1'));

        return $g !== '' && $g !== '1';
    }

    private function passengerRowsTotal(array $filled): float
    {
        $s = 0.0;
        foreach ($filled as $r) {
            $s += (float) ($r['unit_price'] ?? 0) + (float) ($r['extra_fee'] ?? 0);
        }

        return $s;
    }
}
