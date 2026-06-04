<?php

namespace App\Actions;

use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramDayGeneratorService;
use App\Services\TransportProgram\TpAuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateProgramDateRangeAction
{
    public function __construct(
        private readonly ProgramDayGeneratorService $dayGenerator,
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(TpProgram $program, array $data, ?int $actorId): TpProgram
    {
        return DB::transaction(function () use ($program, $data, $actorId) {
            $oldDates = $this->dayGenerator->expandDateRange($program);
            $program->fill($data);
            $program->save();
            $newDates = $this->dayGenerator->expandDateRange($program->fresh());

            $this->dayGenerator->regenerate($program, $oldDates, $newDates);
            $this->audit->log($actorId, 'program.date_range_updated', $program, $program);

            return $program->fresh();
        });
    }
}
