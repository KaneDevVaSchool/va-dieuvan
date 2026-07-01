<?php

namespace App\Services\Requests;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Loại chuyến*',
        'Nơi đi*',
        'Nơi đến*',
        'Ngày giờ đi*',
        'Ngày giờ về',
        'Số hành khách',
        'Ghi chú',
        'Trạng thái',
        'Giá dịch vụ',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['passenger', 'Cơ sở Q.7', 'Sân bay Tân Sơn Nhất', '2026-08-01 07:00', '2026-08-01 09:00', '4', 'Đón ban giám hiệu', 'pending', ''],
        ['cargo', 'Kho Bình Thạnh', 'Cơ sở Gò Vấp', '2026-08-02 08:00', '', '', 'Chuyển tài liệu', 'done', '250000'],
        ['business', 'Văn phòng Q.1', 'Trường VA Q.7', '2026-08-03 13:00', '2026-08-03 14:30', '2', '', 'pending', ''],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import phiếu đề xuất')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Phiếu đề xuất');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'request_import_sample_');
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

        $widths = [14, 24, 24, 18, 18, 14, 32, 14, 14];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import phiếu đề xuất điều vận',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Loại chuyến: passenger | cargo | business.',
            '• Ngày giờ: yyyy-MM-dd HH:mm hoặc dd/MM/yyyy HH:mm (24 giờ).',
            '• Trạng thái: pending | approved | done | cancelled — để trống mặc định là pending.',
            '• Số hành khách: số nguyên, để trống nếu không có.',
            '• Giá dịch vụ: số thực, không nhập dấu phẩy ngàn.',
            '• Mỗi dòng tạo ra một phiếu đề xuất riêng biệt.',
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
