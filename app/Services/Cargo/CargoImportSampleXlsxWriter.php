<?php

namespace App\Services\Cargo;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CargoImportSampleXlsxWriter
{
    private const HEADER_BG = 'FF3A3A5C';

    /** @var list<string> */
    public const HEADERS = [
        'Mã đơn hàng',
        'Tên người gửi*',
        'Tên người nhận*',
        'Địa chỉ lấy hàng*',
        'Địa chỉ giao hàng*',
        'Khối lượng (gram)',
        'Số lượng kiện',
        'Hạn giao (SLA)',
        'Trạng thái',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['CGO-00000001', 'Nguyễn Văn A', 'Trần Thị B', 'Kho Bình Thạnh, Q.Bình Thạnh', 'Trường VA Q.7, P.Tân Phong', '2500', '3', '2026-08-01', 'pending'],
        ['', 'Lê Văn C', 'Phạm Thị D', 'Văn phòng Q.1', 'Cơ sở Gò Vấp', '500', '1', '', 'delivered'],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import đơn hàng')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Đơn hàng');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'cargo_import_sample_');
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

        $widths = [14, 22, 22, 30, 30, 16, 12, 16, 14];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension($this->colLetter($i))->setWidth($w);
        }
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $lines = [
            'Hướng dẫn import đơn hàng vận chuyển',
            '',
            '• Cột có dấu * là bắt buộc.',
            '• Mã đơn hàng: để trống sẽ tự sinh mã CGO-XXXXXXXX.',
            '• Khối lượng: tính theo gram (1 kg = 1000 gram).',
            '• Hạn giao (SLA): yyyy-MM-dd, để trống sẽ dùng +3 giờ từ lúc import.',
            '• Trạng thái: pending | picked_up | in_transit | delivered — để trống mặc định là pending.',
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
