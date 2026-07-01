<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\ImportRequestsRequest;
use App\Services\Requests\RequestSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class RequestImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly RequestSpreadsheetImportService $importService,
    ) {}

    public function store(ImportRequestsRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path, $request->user()?->id);

        return $this->ok($result);
    }
}
