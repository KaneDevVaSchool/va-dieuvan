<?php

namespace App\Services\TpImport;

use App\Models\TpImportBatch;
use App\Models\TpImportRow;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ImportParserService
{
    public function parse(TpImportBatch $batch): TpImportBatch
    {
        $batch->update(['status' => 'parsing']);

        $path = Storage::path($batch->stored_path);
        $reader = $batch->file_type === 'csv' ? new CsvReader() : new XlsxReader();
        $reader->open($path);

        $header = [];
        $rowNumber = 0;
        $total = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($c) => $c->getValue(), $row->getCells());
                $rowNumber++;

                if ($rowNumber === 1) {
                    $header = array_map(fn ($v) => trim((string) $v), $cells);

                    continue;
                }

                if (count(array_filter($cells, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $raw = [];
                foreach ($header as $i => $col) {
                    $raw[$col !== '' ? $col : "col_{$i}"] = $cells[$i] ?? null;
                }

                TpImportRow::query()->create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber - 1,
                    'raw_data' => $raw,
                    'validation_status' => 'pending',
                    'import_status' => 'pending',
                    'created_at' => now(),
                ]);
                $total++;
            }
            break; // chỉ đọc sheet đầu (EC-18)
        }

        $reader->close();

        $batch->update([
            'header_row' => $header,
            'total_rows' => $total,
            'status' => 'parsed',
        ]);

        return $batch->fresh();
    }
}
