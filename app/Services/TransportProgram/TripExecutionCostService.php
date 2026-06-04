<?php

namespace App\Services\TransportProgram;

use App\Models\TpTripExecution;

class TripExecutionCostService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
    ) {}

    public function updateActualCost(TpTripExecution $execution, ?float $cost, ?string $notes, ?int $actorId): TpTripExecution
    {
        $before = $execution->only(['actual_cost', 'cost_notes']);
        $execution->update([
            'actual_cost' => $cost,
            'cost_notes' => $notes,
            'cost_confirmed_by' => $actorId,
            'cost_confirmed_at' => now(),
        ]);
        $this->audit->log($actorId, 'execution.cost_updated', $execution, $execution->program, $before, $execution->only(['actual_cost', 'cost_notes']));

        return $execution->fresh();
    }
}
