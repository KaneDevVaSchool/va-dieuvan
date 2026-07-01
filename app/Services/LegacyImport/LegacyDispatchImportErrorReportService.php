<?php

namespace App\Services\LegacyImport;

use App\Models\DispatchImportBatch;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class LegacyDispatchImportErrorReportService
{
    /**
     * @param  list<array<string,mixed>>  $issues
     */
    public function generate(DispatchImportBatch $batch, array $issues): string
    {
        $relativePath = 'legacy-dispatch-imports/reports/batch_'.$batch->id.'_'.now()->format('YmdHis').'.xlsx';
        $fullPath = Storage::path($relativePath);
        @mkdir(dirname($fullPath), 0775, true);

        $writer = new Writer;
        $writer->openToFile($fullPath);
        $writer->getCurrentSheet()->setName('Chi tiết');

        $writer->addRow(Row::fromValues(['Sheet', 'Dòng Excel', 'Mức', 'Nội dung', 'Gợi ý sửa']));

        foreach ($issues as $issue) {
            $writer->addRow(Row::fromValues([
                $issue['sheet'] ?? '',
                $issue['row'] ?? '',
                $this->levelLabel((string) ($issue['level'] ?? '')),
                $issue['message'] ?? '',
                $issue['hint'] ?? '',
            ]));
        }

        $writer->close();

        $batch->update(['error_report_path' => $relativePath]);

        return $relativePath;
    }

    private function levelLabel(string $level): string
    {
        return match ($level) {
            'error' => 'Lỗi',
            'warning' => 'Cảnh báo',
            'skipped' => 'Bỏ qua',
            default => $level,
        };
    }
}
