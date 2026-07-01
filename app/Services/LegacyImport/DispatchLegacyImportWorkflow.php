<?php

namespace App\Services\LegacyImport;

use App\Models\DispatchImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DispatchLegacyImportWorkflow
{
    public function __construct(
        private readonly LegacyDispatchImportOrchestrator $orchestrator,
        private readonly LegacyDispatchImportErrorReportService $errorReport,
    ) {}

    /**
     * @param  string[]  $sheets
     */
    public function uploadAndAnalyze(User $user, UploadedFile $file, array $sheets): DispatchImportBatch
    {
        $path = $file->store('legacy-dispatch-imports/uploads');
        $normalizedSheets = $this->orchestrator->normalizeSheets($sheets);

        $batch = DispatchImportBatch::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'file_size' => $file->getSize(),
            'selected_sheets' => $normalizedSheets,
            'status' => 'uploaded',
            'imported_by' => $user->id,
        ]);

        return $this->analyze($batch);
    }

    public function analyze(DispatchImportBatch $batch): DispatchImportBatch
    {
        $absolute = storage_path('app/'.$batch->stored_path);
        abort_unless(is_file($absolute), 422, 'Không tìm thấy tệp import trên máy chủ.');

        $diagnostics = new LegacyImportDiagnostics;
        $stats = $this->orchestrator->run(
            $absolute,
            (array) ($batch->selected_sheets ?? []),
            true,
            $diagnostics,
        );

        $issues = $diagnostics->take(500);
        $batch->update([
            'status' => 'analyzed',
            'analyze_stats' => $stats,
            'issues' => $issues,
            'issue_count' => $diagnostics->count(),
        ]);

        if ($issues !== []) {
            $this->errorReport->generate($batch, $issues);
        }

        return $batch->fresh();
    }

    public function execute(DispatchImportBatch $batch): DispatchImportBatch
    {
        abort_unless(in_array($batch->status, ['analyzed', 'failed'], true), 422, 'Lô import không ở trạng thái sẵn sàng ghi dữ liệu.');

        $absolute = storage_path('app/'.$batch->stored_path);
        abort_unless(is_file($absolute), 422, 'Tệp import đã hết hạn — tải lên lại file Excel.');

        $batch->update(['status' => 'executing', 'error_message' => null]);

        try {
            $diagnostics = new LegacyImportDiagnostics;
            $stats = DB::transaction(function () use ($absolute, $batch, $diagnostics) {
                return $this->orchestrator->run(
                    $absolute,
                    (array) ($batch->selected_sheets ?? []),
                    false,
                    $diagnostics,
                );
            });

            $issues = array_merge((array) ($batch->issues ?? []), $diagnostics->take(200));
            $batch->update([
                'status' => 'completed',
                'execute_stats' => $stats,
                'issues' => $issues,
                'issue_count' => count($issues),
                'completed_at' => now(),
            ]);

            if ($diagnostics->count() > 0) {
                $this->errorReport->generate($batch, $diagnostics->take(500));
            }
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $batch->fresh();
    }

    /** @return array<string, mixed> */
    public function serializeBatch(DispatchImportBatch $batch): array
    {
        $analyze = (array) ($batch->analyze_stats ?? []);
        $execute = (array) ($batch->execute_stats ?? []);

        return [
            'id' => $batch->id,
            'original_filename' => $batch->original_filename,
            'file_size' => $batch->file_size,
            'selected_sheets' => $batch->selected_sheets,
            'status' => $batch->status,
            'issue_count' => $batch->issue_count,
            'issues' => $batch->issues ?? [],
            'analyze_stats' => $analyze,
            'execute_stats' => $execute,
            'summary' => $this->buildPlainSummary($batch),
            'has_error_report' => (bool) $batch->error_report_path,
            'error_message' => $batch->error_message,
            'completed_at' => $batch->completed_at?->toIso8601String(),
            'created_at' => $batch->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildPlainSummary(DispatchImportBatch $batch): array
    {
        $stats = $batch->status === 'completed'
            ? (array) ($batch->execute_stats ?? [])
            : (array) ($batch->analyze_stats ?? []);

        $passenger = (array) ($stats['passenger'] ?? []);
        $cargo = (array) ($stats['cargo'] ?? []);

        $willImportRequests = ($passenger['valid'] ?? 0) + ($cargo['valid'] ?? 0);
        $skipped = ($passenger['skipped'] ?? 0) + ($cargo['skipped'] ?? 0);
        $errors = ($passenger['errors'] ?? 0) + ($cargo['errors'] ?? 0);
        $warnings = $batch->issue_count;

        return [
            'will_import_requests' => (int) $willImportRequests,
            'rows_skipped' => (int) $skipped,
            'rows_error' => (int) $errors,
            'notes_count' => (int) $warnings,
            'vehicles_upserted' => (int) (($stats['vehicles']['upserted'] ?? null) ?? 0),
            'is_preview' => $batch->status === 'analyzed',
        ];
    }
}
