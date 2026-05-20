<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyStudent;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Writer\XLSX\Writer;

class PolicyStudentExportService
{
    /**
     * @param  Builder<PolicyStudent>  $query
     */
    public function stream(Builder $query): void
    {
        $writer = PolicyStudentSpreadsheetFormatter::createWriter();
        $writer->openToFile('php://output');

        PolicyStudentSpreadsheetFormatter::beginDataSheet($writer, withBanner: false);

        $index = 0;
        $query->chunk(500, function ($rows) use ($writer, &$index) {
            foreach ($rows as $ps) {
                /** @var PolicyStudent $ps */
                $ps->loadMissing('policyRoute');
                PolicyStudentSpreadsheetFormatter::dataRow($writer, [
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
                ], $index);
                $index++;
            }
        });

        PolicyStudentSpreadsheetFormatter::writeGuideSheet($writer);
        PolicyStudentSpreadsheetFormatter::writeReferenceSheet($writer);

        $writer->close();
    }

    public function streamTemplate(): void
    {
        $writer = PolicyStudentSpreadsheetFormatter::createWriter();
        $writer->openToFile('php://output');

        PolicyStudentSpreadsheetFormatter::beginDataSheet($writer, withBanner: true);
        PolicyStudentSpreadsheetFormatter::writeGuideSheet($writer);
        PolicyStudentSpreadsheetFormatter::writeReferenceSheet($writer);

        $writer->close();
    }
}
