<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyStudent;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class PolicyStudentExportService
{
    /**
     * @param  Builder<PolicyStudent>  $query
     */
    public function stream(Builder $query): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');

        $this->writeDataSheetHeader($writer);

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

        $this->writeGuideSheet($writer);

        $writer->close();
    }

    public function streamTemplate(): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');

        $this->writeDataSheetHeader($writer);
        $this->writeGuideSheet($writer);

        $writer->close();
    }

    private function writeDataSheetHeader(Writer $writer): void
    {
        $writer->getCurrentSheet()->setName('Danh_sach');
        $writer->addRow(Row::fromValues(PolicyStudentSpreadsheetSpec::LABELS_VI));
        $writer->addRow(Row::fromValues(PolicyStudentSpreadsheetSpec::KEYS));
    }

    private function writeGuideSheet(Writer $writer): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Huong_dan');
        $writer->addRow(Row::fromValues(['Cột (key)', 'Nhãn', 'Hướng dẫn']));
        foreach (PolicyStudentSpreadsheetSpec::guideRows() as $g) {
            $writer->addRow(Row::fromValues([$g['key'], $g['label_vi'], $g['hint']]));
        }
    }
}
