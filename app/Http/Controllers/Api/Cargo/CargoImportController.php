<?php

namespace App\Http\Controllers\Api\Cargo;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cargo\ImportCargoShipmentsRequest;
use App\Services\Cargo\CargoSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class CargoImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly CargoSpreadsheetImportService $importService,
    ) {}

    public function store(ImportCargoShipmentsRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path, $request->user()?->id);

        return $this->ok($result);
    }
}
