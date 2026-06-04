<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpAuditLog;
use App\Models\TpProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpAuditController extends Controller
{
    use ApiResponses;

    public function index(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_audit.view')), 403);

        $items = TpAuditLog::query()
            ->where('program_id', $tpProgram->id)
            ->when($request->query('entity_type'), fn ($q, $t) => $q->where('entity_type', $t))
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (TpAuditLog $log) => [
                'id' => $log->id,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'action' => $log->action,
                'actor_name' => $log->actor_name,
                'before_state' => $log->before_state,
                'after_state' => $log->after_state,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all();

        return $this->ok(['items' => $items]);
    }
}
