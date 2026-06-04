<?php

namespace App\Actions;

use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramEnrollmentService;
use Illuminate\Support\Facades\DB;

class EnrollStudentsAction
{
    public function __construct(
        private readonly ProgramEnrollmentService $enrollmentService,
    ) {}

    /**
     * @param  array<int>  $studentIds
     * @return array{enrolled: int, skipped: int}
     */
    public function execute(TpProgram $program, array $studentIds, ?int $actorId): array
    {
        return DB::transaction(fn () => $this->enrollmentService->enrollBulk($program, $studentIds, $actorId));
    }
}
