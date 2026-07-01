<?php

namespace App\Services\Resources;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Biển số xe*',
        'Loại xe',
        'Số chỗ ngồi',
        'Tải trọng (kg)',
        'Chủ xe',
        'Năm sản xuất',
        'Ghi chú',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['51A-12345', 'minibus', '16', '', 'Cty TNHH ABC', '2020', 'Xe đưa đón học sinh'],
        ['51F-67890', 'sedan', '4', '', 'Nguyễn Văn A', '2022', ''],
        ['51B-11111', 'bus', '45', '2000', 'Trường VA', '2019', 'Xe buýt tuyến số 1'],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import xe')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Xe');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'vehicle_import_sample_');
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

        $widths = [16, 14, 12, 14, 24, 14, 32];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import danh sách xe',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Biển số xe đã tồn tại trong hệ thống: bỏ qua dòng (không ghi đè).',
            '• Loại xe: sedan | suv | minibus | bus | truck — để trống mặc định là sedan.',
            '• Số chỗ ngồi: số nguyên.',
            '• Tải trọng (kg): số thực.',
            '• Năm sản xuất: số nguyên bốn chữ số (vd: 2022).',
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
