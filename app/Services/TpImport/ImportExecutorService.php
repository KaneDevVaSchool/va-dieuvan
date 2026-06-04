<?php

namespace App\Services\TpImport;

use App\Models\TpEnrollment;
use App\Models\TpImportBatch;
use App\Models\TpStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportExecutorService
{
    /**
     * @param  array{skip_errors?: bool, include_warnings?: bool, update_existing?: bool, target_program_id?: int}  $options
     * @return array{imported: int, skipped: int, failed: int}
     */
    public function execute(TpImportBatch $batch, array $options): array
    {
        $batch->update(['status' => 'importing', 'settings' => $options]);

        $includeWarnings = $options['include_warnings'] ?? true;
        $updateExisting = $options['update_existing'] ?? false;
        $targetProgramId = $options['target_program_id'] ?? $batch->target_program_id;

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        $statuses = ['valid'];
        if ($includeWarnings) {
            $statuses[] = 'warning';
        }

        $batch->rows()
            ->whereIn('validation_status', $statuses)
            ->where('import_status', 'pending')
            ->orderBy('row_number')
            ->chunkById(200, function ($rows) use (&$imported, &$skipped, &$failed, $updateExisting, $targetProgramId) {
                foreach ($rows as $row) {
                    $data = $row->fixed_data ?? $row->mapped_data ?? [];
                    try {
                        DB::transaction(function () use ($row, $data, $updateExisting, $targetProgramId, &$imported, &$skipped) {
                            $code = trim((string) ($data['code'] ?? '')) ?: 'TP-'.Str::upper(Str::random(8));
                            $existing = TpStudent::query()->where('code', $code)->first();

                            if ($existing && ! $updateExisting) {
                                $row->update(['import_status' => 'skipped', 'student_id' => $existing->id]);
                                $skipped++;

                                return;
                            }

                            $payload = [
                                'full_name' => $data['full_name'] ?? '',
                                'grade' => $data['grade'] ?? null,
                                'class_name' => $data['class_name'] ?? null,
                                'parent_name' => $data['parent_name'] ?? null,
                                'parent_phone' => $data['parent_phone'] ?? null,
                                'address' => $data['address'] ?? null,
                                'source' => 'excel_import',
                            ];

                            $student = TpStudent::query()->updateOrCreate(['code' => $code], $payload);

                            if ($targetProgramId) {
                                TpEnrollment::query()->updateOrCreate(
                                    ['program_id' => $targetProgramId, 'student_id' => $student->id],
                                    ['enrolled_at' => now(), 'unenrolled_at' => null]
                                );
                            }

                            $row->update(['import_status' => 'imported', 'student_id' => $student->id]);
                            $imported++;
                        });
                    } catch (\Throwable $e) {
                        $row->update(['import_status' => 'failed', 'import_error' => $e->getMessage()]);
                        $failed++;
                    }
                }
            });

        $batch->update([
            'imported_rows' => $batch->imported_rows + $imported,
            'skipped_rows' => $batch->skipped_rows + $skipped,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return ['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed];
    }
}
