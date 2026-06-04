<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\ShowAuditLogRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class SystemAuditController extends Controller
{
    use ApiResponses;

    /** Chi tiết log + diff keys cho viewer (§7.2). */
    public function show(ShowAuditLogRequest $request, AuditLog $auditLog): JsonResponse
    {
        $auditLog->load(['actor:id,name,email']);

        return $this->ok([
            'item' => $auditLog,
            'diff' => $this->buildDiff($auditLog->before ?? [], $auditLog->after ?? []),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{key: string, type: string, before: mixed, after: mixed}>
     */
    private function buildDiff(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $out = [];
        foreach ($keys as $key) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;
            if ($b === $a) {
                continue;
            }
            $type = 'changed';
            if (! array_key_exists($key, $before)) {
                $type = 'added';
            } elseif (! array_key_exists($key, $after)) {
                $type = 'removed';
            }
            $out[] = ['key' => $key, 'type' => $type, 'before' => $b, 'after' => $a];
        }

        return $out;
    }
}
