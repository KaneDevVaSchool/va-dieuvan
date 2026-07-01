<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\ImportTpProgramsRequest;
use App\Services\TransportProgram\TpProgramSpreadsheetImportService;
use Illuminate\Http\JsonResponse;

class TpProgramImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpProgramSpreadsheetImportService $importService,
    ) {}

    public function store(ImportTpProgramsRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        abort_unless(is_string($path) && $path !== '', 422, 'Không đọc được tệp tải lên.');

        $result = $this->importService->importFromPath($path, $request->user()?->id);

        return $this->ok($result);
    }
}
