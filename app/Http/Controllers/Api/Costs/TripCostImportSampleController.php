<?php

namespace App\Http\Controllers\Api\Costs;

use App\Http\Controllers\Controller;
use App\Services\Costs\TripCostImportSampleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TripCostImportSampleController extends Controller
{
    public function __construct(
        private readonly TripCostImportSampleXlsxWriter $writer,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('trip.cost.reconcile'), 403);

        $tmpPath = $this->writer->writeTempFile();

        return response()->download($tmpPath, 'mau-import-chi-phi-chuyen.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
