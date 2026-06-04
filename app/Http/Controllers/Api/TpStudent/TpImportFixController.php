<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpImportBatch;
use App\Services\TpImport\ImportAutoFixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpImportFixController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ImportAutoFixService $autoFix,
    ) {}

    public function apply(Request $request, TpImportBatch $tpImportBatch): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.normalize_phone' => ['nullable', 'boolean'],
            'rules.trim_whitespace' => ['nullable', 'boolean'],
            'rules.capitalize_name' => ['nullable', 'boolean'],
            'rules.uppercase_code' => ['nullable', 'boolean'],
        ]);

        $result = $this->autoFix->applyFixes($tpImportBatch, $data['rules']);

        return $this->ok($result);
    }
}
