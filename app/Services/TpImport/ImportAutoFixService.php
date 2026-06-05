<?php

namespace App\Services\TpImport;

use App\Models\TpImportBatch;

class ImportAutoFixService
{
    public function __construct(
        private readonly ImportValidatorService $validator,
    ) {}

    /**
     * @param  array<string, bool>  $rules
     * @return array{fixed: int}
     */
    public function applyFixes(TpImportBatch $batch, array $rules): array
    {
        $fixed = 0;

        $batch->rows()->chunkById(200, function ($rows) use ($rules, &$fixed) {
            foreach ($rows as $row) {
                $data = $row->mapped_data ?? $row->raw_data ?? [];
                $original = $data;

                if (! empty($rules['normalize_phone'])) {
                    foreach (['parent_phone', 'father_phone', 'mother_phone'] as $phoneField) {
                        if (! empty($data[$phoneField])) {
                            $data[$phoneField] = $this->normalizePhone((string) $data[$phoneField]);
                        }
                    }
                }
                if (! empty($rules['trim_whitespace'])) {
                    foreach ($data as $k => $v) {
                        if (is_string($v)) {
                            $data[$k] = trim(preg_replace('/\s+/', ' ', $v));
                        }
                    }
                }
                if (! empty($rules['capitalize_name'])) {
                    foreach (['full_name', 'parent_name', 'father_name', 'mother_name'] as $nameField) {
                        if (! empty($data[$nameField])) {
                            $data[$nameField] = mb_convert_case(mb_strtolower((string) $data[$nameField]), MB_CASE_TITLE, 'UTF-8');
                        }
                    }
                }
                if (! empty($rules['uppercase_code']) && ! empty($data['code'])) {
                    $data['code'] = strtoupper(trim((string) $data['code']));
                }

                if ($data !== $original) {
                    $row->update(['fixed_data' => $data, 'mapped_data' => $data]);
                    $fixed++;
                }
            }
        });

        $batch->update(['auto_fix_rules' => $rules]);
        $this->validator->validate($batch);

        return ['fixed' => $fixed];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            return '+84'.substr($phone, 1);
        }

        return $phone;
    }
}
