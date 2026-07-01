<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Controller;
use App\Services\Trips\TripImportSampleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TripImportSampleController extends Controller
{
    public function __construct(
        private readonly TripImportSampleXlsxWriter $writer,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('trip.manage'), 403);

        $tmpPath = $this->writer->writeTempFile();

        return response()->download($tmpPath, 'mau-import-chuyen-di.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
