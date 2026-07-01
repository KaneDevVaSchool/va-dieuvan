<?php

namespace App\Services\TpStudent;

use App\Models\TpStudent;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;

class TpStudentPurgeService
{
    public function __construct(
        private readonly TpStudentPresenter $presenter,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filterData
     */
    public function purgeAll(User $user, array $filterData, bool $permanent): int
    {
        $q = TpStudent::query();
        $this->presenter->applyListFiltersFromInput($q, $filterData);
        $deleted = 0;

        DB::transaction(function () use ($q, $user, $permanent, &$deleted) {
            foreach ($q->lazyById(100, 'id', 'id') as $student) {
                /** @var TpStudent $student */
                if ($this->purgeOne($user, $student, $permanent)) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    private function purgeOne(User $user, TpStudent $student, bool $permanent): bool
    {
        $before = $student->toArray();

        if ($permanent) {
            $student->forceDelete();
            $this->auditLogger->log(
                actorId: $user->id,
                event: 'tp_student.purge.permanent',
                auditable: null,
                before: $before,
                after: null,
            );

            return true;
        }

        if ($student->trashed()) {
            return false;
        }

        $student->delete();
        $this->auditLogger->log(
            actorId: $user->id,
            event: 'tp_student.purge.soft',
            auditable: $student,
            before: $before,
            after: null,
        );

        return true;
    }
}
