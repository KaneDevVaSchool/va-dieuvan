<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Controller;
use App\Services\Requests\RequestImportSampleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RequestImportSampleController extends Controller
{
    public function __construct(
        private readonly RequestImportSampleXlsxWriter $writer,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('request.manage'), 403);

        $tmpPath = $this->writer->writeTempFile();

        return response()->download($tmpPath, 'mau-import-phieu-de-xuat.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
