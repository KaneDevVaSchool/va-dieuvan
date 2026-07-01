<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\ImportTripsRequest;
use App\Services\Trips\TripSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class TripImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TripSpreadsheetImportService $importService,
    ) {}

    public function store(ImportTripsRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path, $request->user()?->id);

        return $this->ok($result);
    }
}
