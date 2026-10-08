<?php

namespace App\Services\Checklist;

use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Models\ChecklistTemplate;
use App\Models\ExpenseFormSetting;
use App\Support\TripScheduleInstant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * File in phiếu checklist theo mẫu "PHIẾU KIỂM TRA AN TOÀN XE (DAILY)".
 * Với phiếu daily, 7 mục giấy tờ đầu mẫu lấy từ phiếu weekly gần nhất (≤ 14 ngày) hoặc gợi ý từ kho chứng từ.
 */
class ChecklistPrintService
{
    /** Mục giấy tờ đầu mẫu in daily: [nhóm, nội dung, yêu cầu, auto_source]. */
    private const PAPERWORK_ROWS = [
        ['Tài xế - giấy tờ cá nhân', 'GPLX', 'Đúng hạng, còn hiệu lực (Nam: Dưới 58 tuổi / Nữ: Dưới 56 tuổi)', 'driver_doc:license'],
        ['Tài xế - giấy tờ cá nhân', 'CCCD/định danh', 'Còn hiệu lực, mang theo khi công tác', 'driver_doc:id_card'],
        ['Tài xế - giấy tờ cá nhân', 'Giấy khám sức khỏe', 'Còn hiệu lực theo quy định DN/luật (nếu áp dụng)', 'driver_doc:medical_certificate'],
        ['Xe - giấy tờ bắt buộc', 'Đăng ký xe', 'Bản gốc/bản hợp lệ theo quy định, mang trên xe', 'vehicle_doc:registration'],
        ['Xe - giấy tờ bắt buộc', 'Đăng kiểm', 'Còn hiệu lực', 'vehicle_doc:inspection_certificate'],
        ['Xe - giấy tờ bắt buộc', 'Bảo hiểm TNDS', 'Bảo hiểm bắt buộc còn hiệu lực', 'vehicle_doc:insurance_certificate'],
        ['Xe - giấy tờ bắt buộc', 'Bảo hiểm vật chất xe', 'Còn hiệu lực', 'vehicle_doc:insurance_certificate'],
    ];

    public function __construct(private readonly ChecklistPrefillService $prefill) {}

    /**
     * @return array<string, mixed>
     */
    public function data(ChecklistSubmission $s): array
    {
        $s->loadMissing(['template', 'driver', 'vehicle', 'items']);
        $meta = $s->template?->form_meta ?? [];
        $formCode = ExpenseFormSetting::current()->form_codes['checklist'] ?? [];
        $info = $s->trip_info ?? [];

        $rows = [];
        if ($s->period_type === ChecklistTemplate::FREQUENCY_DAILY) {
            $rows = $this->paperworkRows($s);
        }
        foreach ($s->items as $item) {
            $rows[] = [
                'group' => $item->group_name,
                'title' => $item->title,
                'requirement' => $item->requirement,
                'severity' => $item->severity,
                'result' => $item->result,
                'note' => $item->note,
                'checked_at' => $this->local($item->checked_at)?->format('H:i'),
            ];
        }

        $plate = $s->vehicle?->license_plate ?? ($info['license_plate'] ?? '');
        $statement = str_replace(':plate', $plate ?: '..........', (string) ($meta['confirm_statement'] ?? ''));

        return [
            'code' => $s->code,
            'meta' => $meta,
            'form_code' => $formCode,
            'status' => $s->status,
            'result' => $s->result,
            'critical_ng_count' => (int) $s->critical_ng_count,
            'check_date' => $this->local($s->submitted_at ?? $s->started_at)?->format('d/m/Y H:i'),
            'location' => $s->location_text,
            'driver_name' => $s->driver?->full_name ?? ($info['driver_name'] ?? ''),
            'driver_phone' => $s->driver?->phone ?? ($info['driver_phone'] ?? ''),
            'license_plate' => $plate,
            'campus_route' => $info['campus_route'] ?? null,
            'departure_time' => $info['departure_time'] ?? null,
            'attendant' => $info['attendant'] ?? null,
            'inspector_name' => $s->inspector_name,
            'note' => $s->note,
            'statement' => $statement,
            'rows' => $this->withGroupSpans($rows),
        ];
    }

    public function pdf(ChecklistSubmission $s): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.checklist-submission', ['d' => $this->data($s)])->setPaper('a4', 'portrait');
    }

    public function xlsx(ChecklistSubmission $s): string
    {
        $d = $this->data($s);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Checklist');
        $book->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);

        foreach (['A' => 6, 'B' => 22, 'C' => 30, 'D' => 42, 'E' => 9, 'F' => 11, 'G' => 28] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->mergeCells('A1:C1')->setCellValue('A1', $d['meta']['org_line_1'] ?? '');
        $sheet->mergeCells('A2:C2')->setCellValue('A2', $d['meta']['org_line_2'] ?? '');
        $sheet->mergeCells('D1:G1')->setCellValue('D1', 'Kí hiệu: '.($d['form_code']['code'] ?? ''));
        $sheet->mergeCells('D2:G2')->setCellValue('D2', 'Mã phiếu: '.$d['code']);
        $sheet->mergeCells('A3:G3')->setCellValue('A3', $d['meta']['title'] ?? 'PHIẾU KIỂM TRA');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A5:C5')->setCellValue('A5', 'Ngày kiểm tra: '.$d['check_date']);
        $sheet->mergeCells('D5:G5')->setCellValue('D5', 'Địa điểm kiểm tra: '.($d['location'] ?? ''));
        $sheet->mergeCells('A6:C6')->setCellValue('A6', 'Họ và tên Tài xế: '.$d['driver_name']);
        $sheet->mergeCells('D6:G6')->setCellValue('D6', 'Biển số xe: '.$d['license_plate']);
        $sheet->mergeCells('A7:C7')->setCellValue('A7', 'Cơ sở/Tuyến: '.($d['campus_route'] ?? ''));
        $sheet->mergeCells('D7:G7')->setCellValue('D7', 'Giờ xuất bến: '.($d['departure_time'] ?? '').'   Bảo mẫu/Giám sát: '.($d['attendant'] ?? ''));
        $sheet->mergeCells('A8:G8')->setCellValue('A8', 'Mục đích kiểm tra: '.($d['meta']['purpose'] ?? ''));

        $header = ['STT', 'Hạng mục', 'Nội dung kiểm tra', 'Yêu cầu/Quy định', 'Đạt (✓)', 'Không đạt (✗)', 'Ghi chú'];
        $sheet->fromArray($header, null, 'A10');
        $sheet->getStyle('A10:G10')->getFont()->setBold(true);
        $sheet->getStyle('A10:G10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        $r = 11;
        foreach ($d['rows'] as $i => $row) {
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $row['group']);
            $sheet->setCellValue("C{$r}", $row['title'].($row['severity'] === 'critical' ? ' *' : ''));
            $sheet->setCellValue("D{$r}", $row['requirement']);
            $sheet->setCellValue("E{$r}", $row['result'] === ChecklistSubmissionItem::RESULT_OK ? '✓' : ($row['result'] === ChecklistSubmissionItem::RESULT_NA ? 'N/A' : ''));
            $sheet->setCellValue("F{$r}", $row['result'] === ChecklistSubmissionItem::RESULT_NG ? '✗' : '');
            $sheet->setCellValue("G{$r}", trim(($row['note'] ?? '').($row['checked_at'] ? ' ('.$row['checked_at'].')' : '')));
            $r++;
        }
        $last = $r - 1;
        $sheet->getStyle("A10:G{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A10:G{$last}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A11:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E11:F{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r++;
        $sheet->mergeCells("A{$r}:G{$r}")->setCellValue("A{$r}", '(*) Mục Critical — nếu Không đạt thì không khởi hành.');
        $r++;
        $sheet->mergeCells("A{$r}:G{$r}")->setCellValue("A{$r}", 'Kết luận: '.$this->conclusion($d));
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        $sheet->mergeCells("A{$r}:G{$r}")->setCellValue("A{$r}", 'Ghi chú: '.($d['note'] ?? ''));
        $r++;
        $sheet->mergeCells("A{$r}:G{$r}")->setCellValue("A{$r}", $d['statement']);
        $sheet->getStyle("A{$r}")->getAlignment()->setWrapText(true);
        $r += 2;
        $sheet->mergeCells("C{$r}:D{$r}")->setCellValue("C{$r}", 'Người Kiểm tra (nếu có)');
        $sheet->mergeCells("E{$r}:G{$r}")->setCellValue("E{$r}", 'Tài xế Xác nhận');
        $sheet->getStyle("C{$r}:G{$r}")->getFont()->setBold(true);
        $sheet->getStyle("C{$r}:G".($r + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $r++;
        $sheet->mergeCells("C{$r}:D{$r}")->setCellValue("C{$r}", '(ký, ghi rõ họ tên)');
        $sheet->mergeCells("E{$r}:G{$r}")->setCellValue("E{$r}", '(ký, ghi rõ họ tên)');
        $r += 4;
        $sheet->mergeCells("C{$r}:D{$r}")->setCellValue("C{$r}", (string) ($d['inspector_name'] ?? ''));
        $sheet->mergeCells("E{$r}:G{$r}")->setCellValue("E{$r}", $d['driver_name']);

        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        ob_start();
        (new Xlsx($book))->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * Danh sách phiếu (theo bộ lọc) ra Excel cho điều phối.
     *
     * @param  iterable<ChecklistSubmission>  $rows
     */
    public function listXlsx(iterable $rows): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Checklist');

        $header = ['Mã phiếu', 'Loại', 'Kỳ', 'Tài xế', 'SĐT', 'Biển số', 'Trạng thái', 'Kết luận', 'Critical NG', 'Non-critical NG', 'Bắt đầu', 'Nộp lúc', 'Ghi chú'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        $statusLabel = ['draft' => 'Nháp', 'submitted' => 'Đã nộp', 'reviewed' => 'Đã xem'];
        $r = 2;
        foreach ($rows as $s) {
            $sheet->fromArray([
                $s->code,
                $s->template?->name,
                $s->period_key,
                $s->driver?->full_name,
                $s->driver?->phone,
                $s->vehicle?->license_plate,
                $statusLabel[$s->status] ?? $s->status,
                $s->result === 'fail' ? 'Không đạt' : ($s->result === 'pass' ? 'Đạt' : ''),
                (int) $s->critical_ng_count,
                (int) $s->non_critical_ng_count,
                $this->local($s->started_at)?->format('d/m/Y H:i'),
                $this->local($s->submitted_at)?->format('d/m/Y H:i'),
                $s->note,
            ], null, "A{$r}");
            $r++;
        }
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setAutoFilter('A1:M'.max(1, $r - 1));

        ob_start();
        (new Xlsx($book))->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, mixed>  $d
     */
    public function conclusion(array $d): string
    {
        return match (true) {
            $d['status'] === 'draft' => 'Phiếu nháp — chưa nộp.',
            $d['result'] === 'fail' => "KHÔNG ĐỦ ĐIỀU KIỆN khởi hành ({$d['critical_ng_count']} mục Critical không đạt).",
            default => 'ĐỦ ĐIỀU KIỆN khởi hành (tất cả mục Critical đạt).',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paperworkRows(ChecklistSubmission $s): array
    {
        $weekly = ChecklistSubmission::query()
            ->where('driver_id', $s->driver_id)
            ->where('period_type', ChecklistTemplate::FREQUENCY_WEEKLY)
            ->where('status', '!=', ChecklistSubmission::STATUS_DRAFT)
            ->where('submitted_at', '>=', ($s->started_at ?? Carbon::now())->copy()->subDays(14))
            ->orderByDesc('submitted_at')
            ->with('items:id,submission_id,title,result,note')
            ->first();
        $weeklyItems = $weekly?->items->keyBy('title') ?? collect();

        $rows = [];
        foreach (self::PAPERWORK_ROWS as [$group, $title, $requirement, $source]) {
            $w = $weeklyItems->get($title);
            if ($w !== null && $w->result !== null) {
                $rows[] = ['group' => $group, 'title' => $title, 'requirement' => $requirement, 'severity' => 'critical',
                    'result' => $w->result, 'note' => trim(($w->note ? $w->note.' — ' : '').'Theo phiếu '.$weekly->code), 'checked_at' => null];

                continue;
            }

            $rows[] = ['group' => $group, 'title' => $title, 'requirement' => $requirement, 'severity' => 'critical',
                ...$this->paperworkFromDocs($s, $source)];
        }

        return $rows;
    }

    /**
     * @return array{result: ?string, note: ?string, checked_at: null}
     */
    private function paperworkFromDocs(ChecklistSubmission $s, string $source): array
    {
        if ($s->driver === null) {
            return ['result' => null, 'note' => null, 'checked_at' => null];
        }

        $fake = new \App\Models\ChecklistTemplateItem(['auto_source' => $source]);
        $fake->id = 0;
        $hint = $this->prefill->hints(collect([$fake]), $s->driver, $s->vehicle)[0] ?? null;
        if ($hint === null) {
            return ['result' => null, 'note' => null, 'checked_at' => null];
        }

        $note = match ($hint['state']) {
            'missing' => 'Chưa có chứng từ trong hệ thống',
            'exp' => 'Hết hạn '.$this->dmy($hint['expires_at']),
            default => $hint['expires_at'] ? 'Hạn '.$this->dmy($hint['expires_at']) : 'Có chứng từ',
        };

        return ['result' => $hint['suggested'], 'note' => $note.' (kho chứng từ)', 'checked_at' => null];
    }

    /**
     * Gộp ô "Hạng mục" theo nhóm liên tiếp (rowspan cho bản PDF).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function withGroupSpans(array $rows): array
    {
        $n = count($rows);
        for ($i = 0; $i < $n; $i++) {
            if ($i > 0 && $rows[$i]['group'] === $rows[$i - 1]['group']) {
                $rows[$i]['group_span'] = 0;

                continue;
            }
            $span = 1;
            while ($i + $span < $n && $rows[$i + $span]['group'] === $rows[$i]['group']) {
                $span++;
            }
            $rows[$i]['group_span'] = $span;
        }

        return $rows;
    }

    private function local(mixed $value): ?Carbon
    {
        return $value ? Carbon::instance($value)->setTimezone(TripScheduleInstant::DISPLAY_TZ) : null;
    }

    private function dmy(?string $ymd): string
    {
        return $ymd ? Carbon::parse($ymd)->format('d/m/Y') : '';
    }
}
