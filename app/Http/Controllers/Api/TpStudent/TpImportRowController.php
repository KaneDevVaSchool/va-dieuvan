<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportRowController extends Controller
{
    use ApiResponses;

    public function index(Request $request, TpImportBatch $tpImportBatch): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $rows = $tpImportBatch->rows()
            ->when($request->query('status'), fn ($q, $s) => $q->where('validation_status', $s))
            ->orderBy('row_number')
            ->paginate((int) $request->query('per_page', 50));

        return $this->ok([
            'items' => collect($rows->items())->map(fn ($r) => [
                'id' => $r->id,
                'row_number' => $r->row_number,
                'data' => $r->fixed_data ?? $r->mapped_data ?? $r->raw_data,
                'validation_status' => $r->validation_status,
                'validation_errors' => $r->validation_errors,
                'import_status' => $r->import_status,
            ])->all(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
        ]);
    }
}
