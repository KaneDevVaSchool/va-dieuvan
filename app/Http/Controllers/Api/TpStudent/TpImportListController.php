<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportListController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $batches = TpImportBatch::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('imported_by', $user->id))
            ->orderByDesc('created_at')
            ->limit((int) $request->query('limit', 10))
            ->get();

        return $this->ok($batches->map(fn ($b) => [
            'id' => $b->id,
            'original_filename' => $b->original_filename,
            'status' => $b->status,
            'total_rows' => $b->total_rows ?? 0,
            'imported_rows' => $b->imported_rows ?? 0,
            'error_rows' => $b->error_rows ?? 0,
            'has_error_report' => (bool) $b->error_report_path,
            'completed_at' => $b->completed_at?->toISOString(),
            'created_at' => $b->created_at?->toISOString(),
        ])->all());
    }
}
