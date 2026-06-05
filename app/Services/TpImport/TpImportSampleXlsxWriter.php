<?php

namespace App\Services\TpImport;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TpImportSampleXlsxWriter
{
    private const VA_RED = 'FF9A0036';

    private const HEADER_BG = 'FF3A3A5C';

    private const ZEBRA_BG = 'FFFAFAFA';

    /** @var list<string> */
    public const HEADERS = [
        'Họ tên',
        'Mã học sinh',
        'Khối',
        'Lớp',
        'Phụ huynh',
        'SĐT phụ huynh',
        'Địa chỉ',
    ];

    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['Nguyễn Văn An', 'HS001', '3', '3A1', 'Nguyễn Văn Bình', '0901234567', '12 Nguyễn Huệ, Q.1, TP.HCM'],
        ['Trần Thị Bình', 'HS002', '4', '4B2', 'Trần Văn Cường', '0912345678', '45 Lê Lợi, Q.3, TP.HCM'],
        ['Lê Quốc Cường', 'HS003', '5', '5C3', 'Lê Thị Dung', '0923456789', '78 Đinh Tiên Hoàng, Q.BT, TP.HCM'],
    ];

    public function writeTempFile(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Mẫu import học sinh')
            ->setCreator('VA Điều Vận')
            ->setCompany('Vietnam America Schools');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Danh sách học sinh');
        $this->buildDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Hướng dẫn');
        $this->buildGuideSheet($guideSheet);

        $spreadsheet->setActiveSheetIndex(0);

        $tmpPath = tempnam(sys_get_temp_dir(), 'tp_import_sample_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return $tmpPath;
    }

    private function buildDataSheet(Worksheet $ws): void
    {
        $lastCol = 'G';
        $headerRow = 1;
        foreach (self::HEADERS as $i => $label) {
            $col = chr(ord('A') + $i);
            $ws->setCellValue("{$col}{$headerRow}", $label);
        }
        $ws->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::HEADER_BG, 2)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $ws->getRowDimension($headerRow)->setRowHeight(22);

        $dataStart = $headerRow + 1;
        foreach (self::SAMPLE_ROWS as $offset => $row) {
            $r = $dataStart + $offset;
            foreach ($row as $i => $value) {
                $col = chr(ord('A') + $i);
                $ws->setCellValue("{$col}{$r}", $value);
            }
            if ($offset % 2 === 1) {
                $ws->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::ZEBRA_BG, 2)]],
                ]);
            }
        }
        $lastDataRow = $dataStart + count(self::SAMPLE_ROWS) - 1;
        $ws->getStyle("A{$dataStart}:{$lastCol}{$lastDataRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $widths = [28, 14, 8, 10, 22, 16, 42];
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension(chr(ord('A') + $i))->setWidth($w);
        }
        $ws->freezePane('A2');
    }

    private function buildGuideSheet(Worksheet $ws): void
    {
        $ws->getColumnDimension('A')->setWidth(22);
        $ws->getColumnDimension('B')->setWidth(72);

        $lines = [
            ['HƯỚNG DẪN NHẬP FILE MẪU', ''],
            ['', ''],
            ['1. Cách dùng', ''],
            ['', '• Tải file mẫu, mở sheet « Danh sách học sinh ».'],
            ['', '• Không đổi tên / thứ tự các cột ở dòng tiêu đề (dòng 1).'],
            ['', '• Xóa 3 dòng ví dụ hoặc thay bằng dữ liệu thật; thêm dòng mới phía dưới.'],
            ['', '• Lưu file (.xlsx) và tải lên trong màn Import — tối đa 10MB.'],
            ['', ''],
            ['2. Mô tả cột', ''],
            ['Họ tên *', 'Bắt buộc. 2–100 ký tự. Ghi đúng họ tên học sinh.'],
            ['Mã học sinh', 'Khuyến nghị (duy nhất). Nếu để trống, hệ thống tự sinh mã. Dùng để cập nhật học sinh đã có.'],
            ['Khối', 'Khối lớp (vd: 1, 2, 3 … hoặc 10, 11, 12).'],
            ['Lớp', 'Tên lớp (vd: 3A1, 4B2).'],
            ['Phụ huynh', 'Họ tên người liên hệ.'],
            ['SĐT phụ huynh', 'Số Việt Nam: bắt đầu 0 (vd: 090…) hoặc +84. Có thể bật chuẩn hóa SĐT ở bước sửa lỗi.'],
            ['Địa chỉ', 'Địa chỉ đón/trả hoặc liên hệ (một dòng).'],
            ['', ''],
            ['3. Lưu ý', ''],
            ['', '• Dòng trống hoàn toàn sẽ bị bỏ qua.'],
            ['', '• Trùng mã: chọn « Cập nhật » hoặc « Thêm & Cập nhật » trên màn import.'],
            ['', '• Có thể chọn chương trình đích để ghi danh học sinh sau khi nhập.'],
        ];

        $row = 1;
        foreach ($lines as [$a, $b]) {
            $ws->setCellValue("A{$row}", $a);
            $ws->setCellValue("B{$row}", $b);
            if ($row === 1) {
                $ws->mergeCells("A1:B1");
                $ws->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => substr(self::VA_RED, 2)]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $ws->getRowDimension(1)->setRowHeight(32);
            } elseif (str_starts_with($a, '1.') || str_starts_with($a, '2.') || str_starts_with($a, '3.')) {
                $ws->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            } elseif (str_ends_with($a, ' *')) {
                $ws->getStyle("A{$row}")->getFont()->setBold(true);
            }
            $row++;
        }

        $ws->getStyle('B3:B'.($row - 1))->getAlignment()->setWrapText(true);
        $ws->getStyle('A3:B'.($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
