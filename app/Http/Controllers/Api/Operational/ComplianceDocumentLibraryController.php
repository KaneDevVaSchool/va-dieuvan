<?php

namespace App\Http\Controllers\Api\Operational;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\ListComplianceDocumentsRequest;
use App\Services\Resources\ComplianceDocumentLibraryService;

/**
 * Kho chứng từ — xem tập trung, preview / tải file qua GET /attachments/{id}/download.
 */
class ComplianceDocumentLibraryController extends Controller
{
    use ApiResponses;

    public function index(ListComplianceDocumentsRequest $request, ComplianceDocumentLibraryService $library)
    {
        $filters = $request->validated();

        return $this->ok($library->paginate($filters['owner_type'], $filters));
    }
}
