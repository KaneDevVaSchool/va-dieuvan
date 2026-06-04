<?php

namespace App\Services\TransportProgram;

use App\Models\TpAuditLog;
use App\Models\TpProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TpAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        ?int $actorId,
        string $action,
        Model $entity,
        ?TpProgram $program = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?string $actorName = null,
    ): void {
        $actorName ??= $actorId
            ? User::query()->whereKey($actorId)->value('name')
            : null;

        TpAuditLog::query()->create([
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'program_id' => $program?->getKey() ?? ($entity instanceof TpProgram ? $entity->getKey() : null),
            'action' => $action,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'before_state' => $before,
            'after_state' => $after,
            'metadata' => $metadata ?? [],
            'created_at' => now(),
        ]);
    }
}
