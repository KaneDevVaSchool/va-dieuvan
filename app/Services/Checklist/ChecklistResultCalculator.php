<?php

namespace App\Services\Checklist;

use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Models\ChecklistTemplateItem;

/**
 * Kết luận phiếu: có ≥ 1 mục Critical = NG → không đủ điều kiện khởi hành (fail).
 */
final class ChecklistResultCalculator
{
    /**
     * @param  iterable<ChecklistSubmissionItem|array{severity: string, result: ?string}>  $items
     * @return array{result: string, critical_ng: int, non_critical_ng: int, unanswered: int}
     */
    public static function calculate(iterable $items): array
    {
        $criticalNg = 0;
        $nonCriticalNg = 0;
        $unanswered = 0;

        foreach ($items as $item) {
            $severity = is_array($item) ? ($item['severity'] ?? null) : $item->severity;
            $result = is_array($item) ? ($item['result'] ?? null) : $item->result;

            if ($result === null || $result === '') {
                $unanswered++;

                continue;
            }
            if ($result !== ChecklistSubmissionItem::RESULT_NG) {
                continue;
            }
            if ($severity === ChecklistTemplateItem::SEVERITY_CRITICAL) {
                $criticalNg++;
            } else {
                $nonCriticalNg++;
            }
        }

        return [
            'result' => $criticalNg > 0 ? ChecklistSubmission::RESULT_FAIL : ChecklistSubmission::RESULT_PASS,
            'critical_ng' => $criticalNg,
            'non_critical_ng' => $nonCriticalNg,
            'unanswered' => $unanswered,
        ];
    }
}
