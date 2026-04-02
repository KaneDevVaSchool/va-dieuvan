<?php

namespace App\Services\Auditing;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string,mixed>|null  $before
     * @param  array<string,mixed>|null  $after
     * @param  array<string,mixed>|null  $metadata
     */
    public function log(
        ?int $actorId,
        string $event,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): void {
        AuditLog::create([
            'actor_id' => $actorId,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
        ]);
    }
}

