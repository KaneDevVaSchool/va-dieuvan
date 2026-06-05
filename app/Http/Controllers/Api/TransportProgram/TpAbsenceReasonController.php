<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpAbsenceReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpAbsenceReasonController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.view') || $user->can('tp_attendance.manage')), 403);

        $items = TpAbsenceReason::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['code', 'label_vi', 'default_category'])
            ->all();

        return $this->ok(['items' => $items]);
    }
}
