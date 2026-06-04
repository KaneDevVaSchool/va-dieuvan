<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use App\Services\TpImport\ImportErrorReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TpImportErrorReportController extends Controller
{
    public function __construct(
        private readonly ImportErrorReportService $errorReport,
    ) {}

    public function download(Request $request, TpImportBatch $tpImportBatch): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $path = $tpImportBatch->error_report_path;
        if (! $path || ! Storage::exists($path)) {
            $path = $this->errorReport->generate($tpImportBatch);
        }

        return response()->download(Storage::path($path), 'import-report-'.$tpImportBatch->id.'.xlsx');
    }
}
