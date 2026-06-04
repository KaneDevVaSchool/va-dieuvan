<?php

namespace App\Actions;

use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramDayGeneratorService;
use App\Services\TransportProgram\TpAuditLogger;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransportProgramAction
{
    public function __construct(
        private readonly ProgramDayGeneratorService $dayGenerator,
        private readonly TpAuditLogger $audit,
        private readonly TpProgramPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{program: array, day_count: int}
     */
    public function execute(array $data, ?int $actorId): array
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['code'] = $data['code'] ?? 'TP-'.Str::upper(Str::random(8));
            $data['status'] = TpProgram::STATUS_DRAFT;
            $data['created_by'] = $actorId;
            $data['runs_on'] = $data['runs_on'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];

            $program = TpProgram::query()->create($data);
            $dayCount = $this->dayGenerator->generate($program);

            $this->audit->log($actorId, 'program.created', $program, $program, null, $this->presenter->programSummary($program), [
                'day_count' => $dayCount,
            ]);

            return [
                'program' => $this->presenter->programSummary($program->fresh()),
                'day_count' => $dayCount,
            ];
        });
    }
}
