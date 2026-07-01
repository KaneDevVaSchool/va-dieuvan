<?php

namespace App\Services\Trips;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TripImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Ngày giờ đi*',
        'Ngày giờ về',
        'Trạng thái',
        'Biển số xe',
        'Tên tài xế',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['2026-08-01 07:00', '2026-08-01 09:00', 'completed', '51A-12345', 'Nguyễn Văn A'],
        ['2026-08-02 08:30', '2026-08-02 10:00', 'completed', '51F-67890', 'Trần Văn B'],
        ['2026-08-05 13:00', '', 'pending', '', ''],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import chuyến đi')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Chuyến đi');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'trip_import_sample_');
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

        $widths = [18, 18, 14, 16, 24];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import chuyến đi',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Ngày giờ: yyyy-MM-dd HH:mm hoặc dd/MM/yyyy HH:mm (24 giờ).',
            '• Trạng thái: pending | assigned | in_progress | completed — để trống mặc định là pending.',
            '• Biển số xe: dùng để tra cứu xe trong hệ thống, để trống nếu không có.',
            '• Tên tài xế: dùng để tra cứu tài xế theo tên đầy đủ, để trống nếu không có.',
            '• Chuyến được tạo độc lập (không liên kết phiếu đề xuất).',
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
