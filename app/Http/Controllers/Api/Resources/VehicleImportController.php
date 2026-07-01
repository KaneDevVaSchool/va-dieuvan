<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Resources\ImportVehiclesRequest;
use App\Services\Resources\VehicleSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class VehicleImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly VehicleSpreadsheetImportService $importService,
    ) {}

    public function store(ImportVehiclesRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path);

        return $this->ok($result);
    }
}
