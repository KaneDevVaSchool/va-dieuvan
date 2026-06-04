<?php

namespace App\Services\TpImport;

use App\Models\TpImportBatch;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Illuminate\Support\Facades\Storage;

class ImportErrorReportService
{
    public function generate(TpImportBatch $batch): string
    {
        $relativePath = 'tp-imports/reports/batch_'.$batch->id.'_'.now()->format('YmdHis').'.xlsx';
        $fullPath = Storage::path($relativePath);
        @mkdir(dirname($fullPath), 0775, true);

        $writer = new Writer();
        $writer->openToFile($fullPath);

        $groups = [
            'error' => 'Lỗi',
            'warning' => 'Cảnh báo',
            'valid' => 'Thành công',
        ];

        $first = true;
        foreach ($groups as $status => $label) {
            if ($first) {
                $writer->getCurrentSheet()->setName($label);
                $first = false;
            } else {
                $sheet = $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName($label);
            }

            $writer->addRow(Row::fromValues(['Dòng', 'Mã', 'Họ tên', 'Ghi chú']));

            $batch->rows()->where('validation_status', $status)->orderBy('row_number')->each(function ($row) use ($writer) {
                $data = $row->fixed_data ?? $row->mapped_data ?? $row->raw_data ?? [];
                $notes = collect($row->validation_errors ?? [])->map(fn ($e) => $e['message'] ?? '')->implode('; ');
                $writer->addRow(Row::fromValues([
                    $row->row_number,
                    $data['code'] ?? '',
                    $data['full_name'] ?? '',
                    $notes ?: ($row->import_error ?? ''),
                ]));
            });
        }

        $writer->close();

        $batch->update(['error_report_path' => $relativePath]);

        return $relativePath;
    }
}
