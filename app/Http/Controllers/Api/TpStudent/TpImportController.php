<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TpStudent\TpImportStoreRequest;
use App\Models\TpImportBatch;
use App\Services\TpImport\ImportParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ImportParserService $parser,
    ) {}

    public function store(TpImportStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === '' && str_ends_with(strtolower($file->getClientOriginalName()), '.xlsx')) {
            $ext = 'xlsx';
        }
        $path = $file->store('tp-imports/uploads');

        $batch = TpImportBatch::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => in_array($ext, ['xlsx', 'xls', 'csv'], true) ? $ext : 'xlsx',
            'target_program_id' => $request->input('target_program_id'),
            'imported_by' => $user->id,
            'status' => 'uploaded',
        ]);

        $batch = $this->parser->parse($batch);

        return $this->created($this->payload($batch));
    }

    public function show(Request $request, TpImportBatch $tpImportBatch): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        return $this->ok($this->payload($tpImportBatch));
    }

    private function payload(TpImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'original_filename' => $batch->original_filename,
            'file_type' => $batch->file_type,
            'status' => $batch->status,
            'total_rows' => $batch->total_rows,
            'header_row' => $batch->header_row,
            'column_mapping' => $batch->column_mapping,
            'valid_rows' => $batch->valid_rows,
            'warning_rows' => $batch->warning_rows,
            'error_rows' => $batch->error_rows,
            'imported_rows' => $batch->imported_rows,
            'skipped_rows' => $batch->skipped_rows,
            'target_program_id' => $batch->target_program_id,
            'has_error_report' => (bool) $batch->error_report_path,
        ];
    }
}
