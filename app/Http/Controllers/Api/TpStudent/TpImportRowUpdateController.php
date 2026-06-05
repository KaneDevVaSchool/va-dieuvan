<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use App\Models\TpImportRow;
use App\Services\TpImport\ImportValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportRowUpdateController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ImportValidatorService $validator,
    ) {}

    public function __invoke(Request $request, TpImportBatch $tpImportBatch, TpImportRow $tpImportRow): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);
        abort_unless((int) $tpImportRow->batch_id === (int) $tpImportBatch->id, 404);

        if ($request->has('skip')) {
            $skip = $request->boolean('skip');
            $tpImportRow->update(['import_status' => $skip ? 'skipped' : 'pending']);
            $counts = $this->validator->refreshBatchRowCounts($tpImportBatch);

            return $this->ok([
                'row' => $this->rowPayload($tpImportRow->fresh()),
                ...$counts,
            ]);
        }

        $payload = $request->validate([
            'data' => ['required', 'array'],
            'data.full_name' => ['nullable', 'string', 'max:100'],
            'data.code' => ['nullable', 'string', 'max:64'],
            'data.grade' => ['nullable', 'string', 'max:32'],
            'data.class_name' => ['nullable', 'string', 'max:64'],
            'data.parent_name' => ['nullable', 'string', 'max:255'],
            'data.parent_phone' => ['nullable', 'string', 'max:32'],
            'data.address' => ['nullable', 'string', 'max:2000'],
            'data.gender' => ['nullable', 'string', 'max:32'],
            'data.date_of_birth' => ['nullable', 'string', 'max:32'],
            'data.father_name' => ['nullable', 'string', 'max:255'],
            'data.father_phone' => ['nullable', 'string', 'max:32'],
            'data.mother_name' => ['nullable', 'string', 'max:255'],
            'data.mother_phone' => ['nullable', 'string', 'max:32'],
            'data.pickup_point' => ['nullable', 'string', 'max:255'],
            'data.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = $this->validator->applyManualRowEdit($tpImportRow, $payload['data']);
        $counts = $this->validator->refreshBatchRowCounts($tpImportBatch);

        return $this->ok([
            'row' => $this->rowPayload($row),
            ...$counts,
        ]);
    }

    private function rowPayload(TpImportRow $row): array
    {
        return [
            'id' => $row->id,
            'row_number' => $row->row_number,
            'data' => $row->fixed_data ?? $row->mapped_data ?? [],
            'validation_status' => $row->validation_status,
            'validation_errors' => $row->validation_errors,
            'import_status' => $row->import_status,
        ];
    }
}
