<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Controller;
use App\Services\TransportProgram\TpProgramImportSampleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TpProgramImportSampleController extends Controller
{
    public function __construct(
        private readonly TpProgramImportSampleXlsxWriter $writer,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('tp_program.manage'), 403);

        $tmpPath = $this->writer->writeTempFile();

        return response()->download($tmpPath, 'mau-import-chuong-trinh.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
