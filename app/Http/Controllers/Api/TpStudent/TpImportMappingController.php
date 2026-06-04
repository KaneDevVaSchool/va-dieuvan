<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use App\Services\TpImport\ImportValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportMappingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ImportValidatorService $validator,
    ) {}

    public function update(Request $request, TpImportBatch $tpImportBatch): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $data = $request->validate([
            'column_mapping' => ['required', 'array'],
        ]);

        abort_unless(! empty($data['column_mapping']['full_name']), 422, 'Trường Họ tên là bắt buộc.');

        $tpImportBatch->update(['column_mapping' => $data['column_mapping']]);
        $result = $this->validator->validate($tpImportBatch);

        return $this->ok($result);
    }
}
