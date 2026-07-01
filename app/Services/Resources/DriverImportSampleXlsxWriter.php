<?php

namespace App\Services\Resources;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DriverImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Họ tên*',
        'Số điện thoại',
        'Email',
        'CCCD/CMND',
        'Hạng bằng lái',
        'Ngày hết hạn bằng lái',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['Nguyễn Văn An', '0901234567', 'an.nguyen@example.com', '079123456789', 'D', '2028-12-31'],
        ['Trần Thị Bình', '0912345678', '', '079987654321', 'B2', '2027-06-30'],
        ['Lê Văn Công', '0923456789', 'cong.le@example.com', '', 'C', ''],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import tài xế')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Tài xế');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'driver_import_sample_');
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

        $widths = [24, 16, 28, 16, 14, 20];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import danh sách tài xế',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Họ tên đã tồn tại (trùng họ tên + số điện thoại): bỏ qua dòng.',
            '• Hạng bằng lái: A1 | A2 | B1 | B2 | C | D | E | F.',
            '• Ngày hết hạn bằng lái: yyyy-MM-dd hoặc dd/MM/yyyy.',
            '• Tài xế được tạo với trạng thái sẵn sàng (available).',
            '• Liên kết tài khoản hệ thống có thể thực hiện sau khi import.',
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
