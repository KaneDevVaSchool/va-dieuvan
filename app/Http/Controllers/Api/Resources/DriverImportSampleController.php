<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Services\Resources\DriverImportSampleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverImportSampleController extends Controller
{
    public function __construct(
        private readonly DriverImportSampleXlsxWriter $writer,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless((bool) $request->user(), 403);

        $tmpPath = $this->writer->writeTempFile();

        return response()->download($tmpPath, 'mau-import-tai-xe.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
