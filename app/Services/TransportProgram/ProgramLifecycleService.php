<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;

class ProgramLifecycleService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
    ) {}

    public function activate(TpProgram $program, ?int $actorId): TpProgram
    {
        abort_unless($program->status === TpProgram::STATUS_DRAFT || $program->status === TpProgram::STATUS_PAUSED, 422, 'Không thể kích hoạt từ trạng thái hiện tại.');

        $before = $program->only(['status']);
        $program->update(['status' => TpProgram::STATUS_ACTIVE]);
        $this->audit->log($actorId, 'program.activated', $program, $program, $before, $program->only(['status']));

        return $program->fresh();
    }

    public function pause(TpProgram $program, ?int $actorId): TpProgram
    {
        abort_unless($program->status === TpProgram::STATUS_ACTIVE, 422, 'Chỉ chương trình active mới pause được.');

        $before = $program->only(['status']);
        $program->update(['status' => TpProgram::STATUS_PAUSED]);
        $this->audit->log($actorId, 'program.paused', $program, $program, $before, $program->only(['status']));

        return $program->fresh();
    }

    public function cancel(TpProgram $program, ?string $reason, ?int $actorId): TpProgram
    {
        abort_if($program->status === TpProgram::STATUS_CANCELLED, 422, 'Đã hủy rồi.');

        $before = $program->only(['status']);
        $program->update([
            'status' => TpProgram::STATUS_CANCELLED,
            'notes' => trim(($program->notes ?? '')."\nCancelled: ".($reason ?? '')),
        ]);
        $this->audit->log($actorId, 'program.cancelled', $program, $program, $before, $program->only(['status']), [
            'reason' => $reason,
        ]);

        return $program->fresh();
    }
}
