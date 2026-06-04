<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use App\Services\TpImport\ImportErrorReportService;
use App\Services\TpImport\ImportExecutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportExecuteController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ImportExecutorService $executor,
        private readonly ImportErrorReportService $errorReport,
    ) {}

    public function execute(Request $request, TpImportBatch $tpImportBatch): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $options = $request->validate([
            'skip_errors' => ['nullable', 'boolean'],
            'include_warnings' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
            'target_program_id' => ['nullable', 'integer', 'exists:tp_programs,id'],
        ]);

        $result = $this->executor->execute($tpImportBatch, $options);

        if ($tpImportBatch->error_rows > 0) {
            $this->errorReport->generate($tpImportBatch);
        }

        return $this->ok($result);
    }
}
