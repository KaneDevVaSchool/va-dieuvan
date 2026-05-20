<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyStudent;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class PolicyStudentExportService
{
    /** @var list<string> */
    private const HEADERS = [
        'route_name',
        'student_code',
        'student_name',
        'class_name',
        'direction',
        'policy_type',
        'contract_number',
        'sbs_contract',
        'effective_from',
        'effective_to',
        'is_active',
        'policy_note',
    ];

    /**
     * @param  Builder<PolicyStudent>  $query
     */
    public function stream(Builder $query): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');

        $writer->addRow(Row::fromValues(self::HEADERS));

        $query->chunk(500, function ($rows) use ($writer) {
            foreach ($rows as $ps) {
                /** @var PolicyStudent $ps */
                $ps->loadMissing('policyRoute');
                $writer->addRow(Row::fromValues([
                    $ps->policyRoute?->name ?? '',
                    $ps->student_code,
                    $ps->student_name,
                    $ps->class_name ?? '',
                    $ps->direction,
                    $ps->policy_type,
                    $ps->contract_number ?? '',
                    $ps->sbs_contract ?? '',
                    $ps->effective_from?->format('Y-m-d') ?? '',
                    $ps->effective_to?->format('Y-m-d') ?? '',
                    $ps->is_active ? '1' : '0',
                    $ps->policy_note ?? '',
                ]));
            }
        });

        $writer->close();
    }

    public function streamTemplate(): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');
        $writer->addRow(Row::fromValues(self::HEADERS));
        $writer->close();
    }
}
