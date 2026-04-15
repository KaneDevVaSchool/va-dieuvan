<?php

namespace App\Services\DispatchRequest;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PdfMpdf;

/**
 * Điền mẫu Excel BM.02/MH.QT.04 (P2P) từ wizard_snapshot và xuất PDF (qua mPDF).
 *
 * Thứ tự đối tượng khớp `targetOptions` trên form (31 mục); ô tick theo map P2P_TARGET_CHECKBOX_CELLS.
 * Ô C13 chỉ nhận nội dung textarea «Mục đích sử dụng» (form.purpose).
 */
class Bm02P2pFormGenerator
{
    /** Ký tự hiển thị trong ô (không dùng boolean Excel — tránh hiện chữ TRUE/FALSE). */
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

    /** Ô boolean trên mẫu gốc nhưng không map vào `targetOptions` (ví dụ cột thừa hàng cuối). */
    private const P2P_EXTRA_TEMPLATE_BOOL_CELLS = [
        'K32',
    ];

    /**
     * @param  array{form?: array, passengerRows?: array<int, array>, businessRows?: array<int, array>, cargoRows?: array<int, array>}  $wizard
     * @return array{xlsx: string, pdf: string}
     */
    public function generate(array $wizard): array
    {
        $path = $this->templatePath();
        $reader = IOFactory::createReader('Xlsx');
        /** @var Spreadsheet $ss */
        $ss = $reader->load($path);
        $sheet = $ss->getActiveSheet();

        $ss->getProperties()
            ->setTitle('Đề nghị điều vận BM.02')
            ->setSubject('BM.02/MH.QT.04');

        $form = $wizard['form'] ?? [];
        $rows = $wizard['passengerRows'] ?? [];
        $targets = $form['targets'] ?? [];

        $filled = array_values(array_filter(
            is_array($rows) ? $rows : [],
            fn ($r) => is_array($r) && $this->rowHasPassengerContent($r),
        ));

        /** Mẫu gốc dùng kiểu boolean cho ô «tick» — Excel/mPDF hiển thị TRUE/FALSE; ghi đè bằng chuỗi. */
        $this->resetTemplateCheckboxCells($sheet);

        $now = Carbon::now();

        $sheet->setCellValue('N3', ExcelDate::PHPToExcel($now));
        $sheet->getStyle('N3')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        $sheet->setCellValue('E7', (string) ($form['requester_name'] ?? ''));
        $sheet->setCellValue('E8', (string) ($form['requester_email'] ?? ''));
        $sheet->setCellValue('E9', (string) ($form['requester_phone'] ?? ''));
        $sheet->setCellValue('E10', (string) ($form['requester_unit'] ?? ''));

        $sheet->setCellValue('C13', (string) ($form['purpose'] ?? ''));

        $basisNote = '';
        if (! empty($form['basisFileName'])) {
            $basisNote = 'Đính kèm: '.$form['basisFileName'];
        }
        $maxPassengerLinesOnForm = 5;
        if (count($filled) > $maxPassengerLinesOnForm) {
            $n = count($filled);
            $overflowNote = "(Ghi chú: {$n} chuyến đã khai báo — trên mẫu in hiển thị tối đa {$maxPassengerLinesOnForm} dòng đầu; toàn bộ nằm trong portal.)";
            $basisNote = $basisNote !== '' ? $basisNote."\n\n".$overflowNote : $overflowNote;
        }
        $sheet->setCellValue('C14', $basisNote !== '' ? $basisNote : '');

        $prop = $form['proposed_date'] ?? '';
        $need = $form['date_needed'] ?? '';
        $sheet->setCellValue('E17', $this->formatDateCell($prop));
        $sheet->setCellValue('E20', $this->formatDateCell($need));

        $urgent = ! empty($form['is_urgent']);
        $sheet->getCell('B21')->setValueExplicit(
            $urgent ? self::CHECKED_MARK : '',
            DataType::TYPE_STRING,
        );
        $sheet->setCellValue('E21', $urgent ? (string) ($form['urgent_reason'] ?? '') : '');

        $this->applyTargetCheckboxes($sheet, is_array($targets) ? $targets : []);

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
        if ($coord !== []) {
            $sheet->setCellValue('E33', implode(' — ', $coord));
        }

        $startDataRow = 40;
        $maxLines = $maxPassengerLinesOnForm;
        for ($i = 0; $i < $maxLines; $i++) {
            $r = $filled[$i] ?? null;
            $excelRow = $startDataRow + $i;
            if (! is_array($r)) {
                continue;
            }

            $sheet->setCellValue("C{$excelRow}", $this->formatDateTimeCell($r['depart_at'] ?? ''));
            $sheet->setCellValue("D{$excelRow}", (string) ($r['pickup'] ?? ''));
            $sheet->setCellValue("E{$excelRow}", $this->formatDateTimeCell($r['return_at'] ?? ''));
            $sheet->setCellValue("G{$excelRow}", (string) ($r['dropoff'] ?? ''));
            $sheet->setCellValue("I{$excelRow}", $this->numOrEmpty($r['guests'] ?? ''));
            $sheet->setCellValue("J{$excelRow}", (string) ($r['person_in_charge'] ?? ''));
            $sheet->setCellValue("K{$excelRow}", $this->numOrEmpty($r['unit_price'] ?? ''));
            $sheet->setCellValue("L{$excelRow}", $this->numOrEmpty($r['extra_fee'] ?? ''));
            $unit = (float) ($r['unit_price'] ?? 0);
            $extra = (float) ($r['extra_fee'] ?? 0);
            $sheet->setCellValue("M{$excelRow}", $unit + $extra > 0 ? $unit + $extra : '');
            $sheet->setCellValue("N{$excelRow}", (string) ($r['notes'] ?? ''));
        }

        $total = $this->passengerRowsTotal($filled);
        if ($total > 0) {
            $sheet->setCellValue('L45', $total);
            $sheet->getStyle('L45')->getNumberFormat()->setFormatCode('#,##0');
        }

        $requester = (string) ($form['requester_name'] ?? '');
        if ($requester !== '') {
            $sheet->setCellValue('E55', $requester);
        }
        $sheet->setCellValue('D55', ' ');
        $sheet->setCellValue('I55', ' ');

        $writerXlsx = IOFactory::createWriter($ss, 'Xlsx');
        ob_start();
        $writerXlsx->save('php://output');
        $xlsxBinary = ob_get_clean();

        $pdfWriter = new PdfMpdf($ss);
        $pdfWriter->setFont('dejavusans');
        $pdfWriter->setUseInlineCss(true);
        ob_start();
        $pdfWriter->save('php://output');
        $pdfBinary = ob_get_clean();

        return ['xlsx' => $xlsxBinary, 'pdf' => $pdfBinary];
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

    /**
     * @param  array<int, string|mixed>  $selected
     */
    private function applyTargetCheckboxes(Worksheet $sheet, array $selected): void
    {
        $selectedNorm = [];
        foreach ($selected as $s) {
            $key = $this->normalizeTargetLabel((string) $s);
            if ($key !== '') {
                $selectedNorm[$key] = true;
            }
        }
        if ($selectedNorm === []) {
            return;
        }

        $checkboxes = self::P2P_TARGET_CHECKBOX_CELLS;
        /** @var array<int, string> $labels — trùng thứ tự form Vue */
        $labels = [
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

        foreach ($labels as $i => $label) {
            $cell = $checkboxes[$i] ?? null;
            if ($cell === null || $cell === '') {
                continue;
            }
            if (empty($selectedNorm[$this->normalizeTargetLabel($label)])) {
                continue;
            }
            $sheet->getCell($cell)->setValueExplicit(self::CHECKED_MARK, DataType::TYPE_STRING);
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

    /**
     * Khớp logic `isPassengerRowFilled` trên form Vue.
     */
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
