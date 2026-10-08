<?php

namespace App\Services\Expenses;

use App\Models\ExpenseDocument;
use App\Models\ExpenseDocumentLine;
use App\Models\ExpenseFormSetting;
use App\Support\TripScheduleInstant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * File in hồ sơ chi phí theo mẫu chuẩn:
 * - PDF: dựng lại bằng Blade (xem / in ngay trên điện thoại).
 * - XLSX: điền vào template cắt từ file mẫu gốc (resources/templates/expenses) — giữ nguyên định dạng, logo.
 */
class ExpensePrintService
{
    private const TEMPLATE_DIR = 'templates/expenses';

    /**
     * @return array<string, mixed>
     */
    public function data(ExpenseDocument $doc): array
    {
        $doc->loadMissing(['lines.vehicle:id,license_plate', 'vehicle:id,license_plate', 'parent:id,code']);
        $settings = ExpenseFormSetting::current();
        $formCodes = $settings->form_codes ?? [];

        $lines = $doc->lines->map(fn (ExpenseDocumentLine $l) => [
            'category' => $l->category,
            'description' => $l->description,
            'license_plate' => $l->vehicle?->license_plate,
            'unit' => $l->unit,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'amount' => (float) $l->amount,
            'purchased_on' => $l->purchased_on?->format('d/m/Y'),
            'seller_name' => $l->seller_name,
            'seller_address' => $l->seller_address,
            'has_invoice' => (bool) $l->has_invoice,
            'note' => $l->note,
        ])->values()->all();

        $plates = collect($lines)->pluck('license_plate')->filter()->unique()->values();
        if ($plates->isEmpty() && $doc->vehicle) {
            $plates = collect([$doc->vehicle->license_plate]);
        }

        $proposedOn = $doc->proposed_on ?? Carbon::now(TripScheduleInstant::DISPLAY_TZ);

        return [
            'doc' => $doc,
            'code' => $doc->code,
            'type_label' => ExpensePresenter::typeLabel($doc),
            'title_suffix' => mb_strtoupper(match ($doc->payment_kind) {
                ExpenseDocument::KIND_ADVANCE => 'tạm ứng',
                ExpenseDocument::KIND_REIMBURSEMENT => 'hoàn ứng',
                default => 'thanh toán',
            }),
            'form_code' => $formCodes[$doc->doc_type] ?? [],
            'settings' => $settings,
            'lines' => $lines,
            'plates' => $plates->implode(', '),
            'total' => (float) $doc->total_amount,
            'amount_in_words' => $doc->amount_in_words,
            'proposed_on' => $proposedOn->format('d/m/Y'),
            'needed_by' => $doc->needed_by?->format('d/m/Y'),
            'date_line' => sprintf('%s, ngày %s tháng %s năm %s', $settings->city_label ?: 'Tp. Hồ Chí Minh', $proposedOn->format('d'), $proposedOn->format('m'), $proposedOn->format('Y')),
            'basis' => $doc->basis ?: $doc->parent?->code,
            'signatories' => $doc->signatories ?? [],
            'payment_method_label' => match ($doc->payment_method) {
                'cash' => 'Tiền mặt',
                'transfer' => 'Chuyển khoản',
                default => '',
            },
            'bank_line' => trim(implode(' — ', array_filter([$doc->bank_account_no, $doc->bank_name, $doc->bank_account_holder]))),
        ];
    }

    public function pdf(ExpenseDocument $doc): \Barryvdh\DomPDF\PDF
    {
        $view = match ($doc->doc_type) {
            ExpenseDocument::TYPE_PROPOSAL => 'pdf.expense-proposal',
            ExpenseDocument::TYPE_NO_INVOICE_STATEMENT => 'pdf.expense-no-invoice-statement',
            default => 'pdf.expense-payment-request',
        };

        $orientation = $doc->doc_type === ExpenseDocument::TYPE_NO_INVOICE_STATEMENT ? 'landscape' : 'portrait';

        return Pdf::loadView($view, ['d' => $this->data($doc)])->setPaper('a4', $orientation);
    }

    public function xlsx(ExpenseDocument $doc): string
    {
        $d = $this->data($doc);
        $book = IOFactory::load(resource_path(self::TEMPLATE_DIR.'/'.$doc->doc_type.'.xlsx'));
        $ws = $book->getActiveSheet();

        match ($doc->doc_type) {
            ExpenseDocument::TYPE_PROPOSAL => $this->fillProposal($ws, $d),
            ExpenseDocument::TYPE_NO_INVOICE_STATEMENT => $this->fillNoInvoice($ws, $d),
            default => $this->fillPaymentRequest($ws, $d),
        };

        ob_start();
        (new Xlsx($book))->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * Mẫu BM.01/MH.QT.01 — dòng hàng hóa 34..36 (chèn thêm nếu > 3 dòng), tổng ở dòng 37.
     *
     * @param  array<string, mixed>  $d
     */
    private function fillProposal(Worksheet $ws, array $d): void
    {
        /** @var ExpenseDocument $doc */
        $doc = $d['doc'];
        $sig = $this->signatoryNames($d['signatories'], 4);

        $ws->setCellValue('I2', 'Kí hiệu: '.($d['form_code']['code'] ?? 'BM.01/MH.QT.01'));
        $ws->setCellValue('I3', 'Ngày ban hành: '.($d['form_code']['issued_on'] ?? ''));
        $ws->setCellValue('I4', 'Lần ban hành: '.($d['form_code']['revision'] ?? '01'));
        $ws->setCellValue('B6', 'A. Người đề xuất   (Mã hồ sơ: '.$d['code'].')');
        $ws->setCellValue('D7', $doc->requester_name);
        $ws->setCellValue('D8', $doc->requester_email);
        $ws->setCellValueExplicit('D9', (string) $doc->requester_phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $ws->setCellValue('D10', $doc->department_label);
        $ws->setCellValue('D13', $doc->purpose);
        $ws->setCellValue('D14', $d['basis']);
        $ws->setCellValue('D17', $d['proposed_on']);
        $ws->setCellValue('F19', $d['needed_by']);
        $ws->setCellValue('D20', $doc->is_urgent ? 'Có — '.($doc->urgent_reason ?? '') : '');
        $ws->setCellValue('E24', $doc->allocation_target);
        $ws->setCellValue('I24', $d['plates']);
        $ws->setCellValue('H25', $doc->allocation_area);
        $ws->setCellValue('E26', $doc->handover_person);

        $first = 34;
        $templateRows = 3;
        $n = max(1, count($d['lines']));
        if ($n > $templateRows) {
            $ws->insertNewRowBefore($first + $templateRows, $n - $templateRows);
            for ($r = $first + $templateRows; $r < $first + $n; $r++) {
                $ws->mergeCells("D{$r}:E{$r}");
            }
        }

        foreach (array_values($d['lines']) as $i => $line) {
            $r = $first + $i;
            $desc = trim(implode("\n", array_filter([
                $line['description'],
                $line['license_plate'] ? 'Xe '.$line['license_plate'] : null,
            ])));
            $ws->setCellValue("B{$r}", $i + 1);
            $ws->setCellValue("C{$r}", $line['category']);
            $ws->setCellValue("D{$r}", $desc);
            $ws->setCellValue("F{$r}", $line['unit']);
            $ws->setCellValue("G{$r}", $line['quantity']);
            $ws->setCellValue("H{$r}", $line['unit_price']);
            $ws->setCellValue("I{$r}", "=H{$r}*G{$r}");
            $ws->setCellValue("J{$r}", $line['note']);
        }
        for ($i = count($d['lines']); $i < max($n, $templateRows); $i++) {
            $r = $first + $i;
            foreach (['B', 'C', 'D', 'F', 'G', 'H', 'I', 'J'] as $col) {
                $ws->setCellValue("{$col}{$r}", null);
            }
        }

        $last = $first + max($n, $templateRows) - 1;
        $totalRow = $last + 1;
        $ws->setCellValue("I{$totalRow}", "=SUM(I{$first}:I{$last})");

        $shift = $totalRow - 37;
        $ws->setCellValue('B'.(49 + $shift), $sig[0]);
        $ws->setCellValue('D'.(49 + $shift), $sig[1]);
        $ws->setCellValue('F'.(49 + $shift), $sig[2]);
        $ws->setCellValue('I'.(49 + $shift), $doc->requester_name);
        $ws->setCellValue('D'.(65 + $shift), $doc->requester_name);
        $ws->setCellValue('F'.(65 + $shift), $sig[1]);
        $ws->setCellValue('I'.(65 + $shift), $sig[3]);
        $ws->setCellValue('C27', $doc->handover_person ?: $doc->requester_name);
    }

    /**
     * Mẫu BM03-PKT-QT-TUTT — Giấy đề nghị thanh toán / tạm ứng / hoàn ứng.
     *
     * @param  array<string, mixed>  $d
     */
    private function fillPaymentRequest(Worksheet $ws, array $d): void
    {
        /** @var ExpenseDocument $doc */
        $doc = $d['doc'];
        $sig = $this->signatoryNames($d['signatories'], 3);
        $content = $doc->purpose ?: collect($d['lines'])->pluck('category')->filter()->unique()->implode(', ');
        if ($d['plates'] !== '') {
            $content .= ' — Xe '.$d['plates'];
        }

        $ws->setCellValue('C1', $d['code']);
        $ws->setCellValue('F3', '             '.($d['form_code']['code'] ?? 'BM03-PKT-QT-TUTT'));
        $ws->setCellValue('E5', $d['settings']->company_name);
        $ws->setCellValue('E6', $doc->department_label);
        $ws->setCellValue('B7', 'GIẤY ĐỀ NGHỊ '.$d['title_suffix']);
        $ws->setCellValue('B8', $d['date_line']);
        $ws->setCellValue('E10', $doc->requester_name);
        $ws->setCellValue('D11', $doc->department_label);
        $ws->setCellValue('D12', $content);
        $ws->setCellValue('E13', $d['basis']);
        $ws->setCellValue('C14', $d['total']);
        $ws->getStyle('C14')->getNumberFormat()->setFormatCode('#,##0" đ"');
        $ws->setCellValue('C15', $d['amount_in_words']);
        $ws->setCellValue('C16', $doc->payment_due_label);
        $ws->setCellValue('C17', $d['payment_method_label']);
        $ws->setCellValue('D18', $d['bank_line']);
        $ws->setCellValue('B26', $doc->requester_name);
        $ws->setCellValue('D26', $sig[0]);
        $ws->setCellValue('F26', $sig[1]);
        $ws->setCellValue('D33', $sig[2]);
    }

    /**
     * Mẫu 01/TNDN — Bảng kê thu mua hàng hóa, dịch vụ mua vào không có hóa đơn. Dòng 15..19, tổng dòng 20.
     *
     * @param  array<string, mixed>  $d
     */
    private function fillNoInvoice(Worksheet $ws, array $d): void
    {
        /** @var ExpenseDocument $doc */
        $doc = $d['doc'];
        $s = $d['settings'];
        $sig = $this->signatoryNames($d['signatories'], 1);

        $ws->setCellValue('B6', $d['date_line']);
        $ws->setCellValue('B7', '- Tên doanh nghiệp: '.$s->company_name);
        $ws->setCellValue('B8', '- Mã số thuế : '.$s->tax_code);
        $ws->setCellValue('B9', '- Địa chỉ: '.$s->company_address);
        $ws->setCellValue('B10', '- Địa chỉ nơi tổ chức thu mua: '.$s->purchasing_place);
        $ws->setCellValue('B11', '- Người phụ trách thu mua: '.($doc->purchasing_person ?: $doc->requester_name).'   (Mã: '.$d['code'].($d['basis'] ? ' — kèm '.$d['basis'] : '').')');

        $first = 15;
        $templateRows = 5;
        $n = max(1, count($d['lines']));
        if ($n > $templateRows) {
            $ws->insertNewRowBefore($first + $templateRows, $n - $templateRows);
        }
        $rowsUsed = max($n, $templateRows);

        for ($i = 0; $i < $rowsUsed; $i++) {
            $r = $first + $i;
            $line = $d['lines'][$i] ?? null;
            $ws->setCellValue("B{$r}", $line['purchased_on'] ?? null);
            $ws->setCellValue("C{$r}", $line['seller_name'] ?? null);
            $ws->setCellValue("D{$r}", $line['seller_address'] ?? null);
            $ws->setCellValue("E{$r}", $line ? trim(implode(' — ', array_filter([$line['category'], $line['description'], $line['license_plate'] ? 'Xe '.$line['license_plate'] : null]))) : null);
            $ws->setCellValue("F{$r}", $line['quantity'] ?? null);
            $ws->setCellValue("G{$r}", $line['unit_price'] ?? null);
            $ws->setCellValue("H{$r}", $line ? "=F{$r}*G{$r}" : null);
            $ws->setCellValue("I{$r}", $line['note'] ?? null);
        }

        $last = $first + $rowsUsed - 1;
        $totalRow = $last + 1;
        $shift = $totalRow - 20;
        $ws->setCellValue("H{$totalRow}", "=SUM(H{$first}:H{$last})");
        $ws->setCellValue('D'.(22 + $shift), $d['amount_in_words']);
        $ws->setCellValue('B'.(32 + $shift), $doc->purchasing_person ?: $doc->requester_name);
        $ws->setCellValue('F'.(32 + $shift), $sig[0]);
    }

    /**
     * @param  list<array{role?: string, name?: string}>  $signatories
     * @return list<string>
     */
    private function signatoryNames(array $signatories, int $count): array
    {
        $names = array_map(fn ($s) => (string) ($s['name'] ?? ''), array_values($signatories));

        return array_pad(array_slice($names, 0, $count), $count, '');
    }
}
