<?php

namespace App\Services\TransportProgram;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TpProgramImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Tên chương trình*',
        'Mã CT',
        'Điểm đi',
        'Điểm đến',
        'Giờ đi*',
        'Giờ về',
        'Ngày bắt đầu*',
        'Ngày kết thúc*',
        'Thứ trong tuần',
        'Chi phí/chuyến',
        'Ghi chú',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['Tuyến Q.7 sáng', 'TP-Q7-AM', 'Sunrise City', 'VA Schools Q.7', '06:30', '07:15', '2026-08-01', '2027-05-31', 'mon,tue,wed,thu,fri', '350000', ''],
        ['Tuyến Bình Thạnh chiều', '', 'Chung cư Vinhomes', 'VA Schools Q.7', '16:00', '17:00', '2026-08-01', '2027-05-31', 'mon,wed,fri', '', 'Ca chiều'],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import chương trình')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Chương trình');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'tp_program_import_sample_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    private function buildDataSheet(Worksheet $ws): void
    {
        $lastCol = $this->colLetter(count(self::HEADERS) - 1);
        $headerRow = 1;
        foreach (self::HEADERS as $i => $label) {
            $ws->setCellValue($this->colLetter($i).$headerRow, $label);
        }
        $ws->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::HEADER_BG, 2)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);

        $rowNum = 2;
        foreach (self::SAMPLE_ROWS as $sample) {
            foreach ($sample as $i => $value) {
                $ws->setCellValue($this->colLetter($i).$rowNum, $value);
            }
            $rowNum++;
        }

        $widths = [28, 14, 22, 22, 10, 10, 14, 14, 22, 14, 24];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import chương trình đưa đón',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Giờ đi/về: định dạng HH:MM (24h).',
            '• Ngày: yyyy-MM-dd hoặc dd/MM/yyyy.',
            '• Thứ trong tuần: mon,tue,wed,thu,fri,sat,sun — để trống = thứ 2–6.',
            '• Mã CT trùng hệ thống: bỏ qua dòng (không ghi đè).',
            '• Chương trình mới được tạo ở trạng thái Nháp và sinh lịch ngày tự động.',
        ];
        foreach ($lines as $i => $line) {
            $ws->setCellValue('A'.($i + 1), $line);
        }
        $ws->getColumnDimension('A')->setWidth(72);
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
}
