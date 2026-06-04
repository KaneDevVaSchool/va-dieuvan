<?php

namespace App\Services\TpImport;

use App\Models\TpImportBatch;
use App\Models\TpImportRow;
use App\Models\TpStudent;

class ImportValidatorService
{
    /**
     * @return array{valid: int, warning: int, error: int}
     */
    public function validate(TpImportBatch $batch): array
    {
        $mapping = $batch->column_mapping ?? [];
        $valid = 0;
        $warning = 0;
        $error = 0;

        $existingCodes = TpStudent::query()->pluck('id', 'code');

        $batch->rows()->orderBy('row_number')->chunkById(200, function ($rows) use ($mapping, &$valid, &$warning, &$error, $existingCodes) {
            foreach ($rows as $row) {
                $mapped = $this->applyMapping($row, $mapping);
                $errors = [];
                $status = 'valid';

                $name = trim((string) ($mapped['full_name'] ?? ''));
                if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
                    $errors[] = ['field' => 'full_name', 'level' => 'error', 'message' => 'Họ tên bắt buộc, 2-100 ký tự.'];
                    $status = 'error';
                }

                $code = trim((string) ($mapped['code'] ?? ''));
                if ($code !== '' && $existingCodes->has($code)) {
                    $errors[] = ['field' => 'code', 'level' => 'warning', 'message' => 'Mã đã tồn tại — sẽ bỏ qua hoặc cập nhật.'];
                    if ($status !== 'error') {
                        $status = 'warning';
                    }
                }

                $phone = trim((string) ($mapped['parent_phone'] ?? ''));
                if ($phone !== '' && ! preg_match('/^(0|\+84)[0-9]{8,11}$/', preg_replace('/\s+/', '', $phone))) {
                    $errors[] = ['field' => 'parent_phone', 'level' => 'warning', 'message' => 'Số điện thoại không đúng định dạng VN.'];
                    if ($status !== 'error') {
                        $status = 'warning';
                    }
                }

                $row->update([
                    'mapped_data' => $mapped,
                    'validation_status' => $status,
                    'validation_errors' => $errors,
                ]);

                match ($status) {
                    'valid' => $valid++,
                    'warning' => $warning++,
                    'error' => $error++,
                };
            }
        });

        $batch->update([
            'valid_rows' => $valid,
            'warning_rows' => $warning,
            'error_rows' => $error,
            'status' => 'mapped',
        ]);

        return ['valid' => $valid, 'warning' => $warning, 'error' => $error];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyMapping(TpImportRow $row, array $mapping): array
    {
        $raw = $row->raw_data ?? [];
        $mapped = [];
        foreach ($mapping as $dbField => $sourceColumn) {
            $mapped[$dbField] = $raw[$sourceColumn] ?? null;
        }

        return $mapped;
    }
}
