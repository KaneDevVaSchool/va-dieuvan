<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;

class TpProgramPurgeService
{
    public function __construct(
        private readonly TpProgramListFilter $listFilter,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filterData
     */
    public function purgeAll(User $user, array $filterData, bool $permanent): int
    {
        $q = $this->listFilter->filteredQuery($filterData);
        $deleted = 0;

        DB::transaction(function () use ($q, $user, $permanent, &$deleted) {
            foreach ($q->lazyById(50, 'id', 'id') as $program) {
                /** @var TpProgram $program */
                if ($this->purgeOne($user, $program, $permanent)) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    private function purgeOne(User $user, TpProgram $program, bool $permanent): bool
    {
        $before = $program->toArray();

        if ($permanent) {
            $program->forceDelete();
            $this->auditLogger->log(
                actorId: $user->id,
                event: 'tp_program.purge.permanent',
                auditable: null,
                before: $before,
                after: null,
            );

            return true;
        }

        if ($program->trashed()) {
            return false;
        }

        $program->delete();
        $this->auditLogger->log(
            actorId: $user->id,
            event: 'tp_program.purge.soft',
            auditable: $program,
            before: $before,
            after: null,
        );

        return true;
    }
}
