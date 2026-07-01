<?php

namespace App\Http\Controllers\Api\Costs;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Costs\ImportTripCostsRequest;
use App\Services\Costs\TripCostSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class TripCostImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TripCostSpreadsheetImportService $importService,
    ) {}

    public function store(ImportTripCostsRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path, $request->user()?->id);

        return $this->ok($result);
    }
}
