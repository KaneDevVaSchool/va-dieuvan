<?php

namespace App\Http\Requests\Api\Checklist;

class ChecklistComplianceRequest extends StaffChecklistRequest
{
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:checklist_templates,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ];
    }

    protected function passedValidation(): void
    {
        $from = strtotime((string) $this->input('date_from'));
        $to = strtotime((string) $this->input('date_to'));
        if ($from !== false && $to !== false && ($to - $from) > 62 * 86400) {
            abort(422, 'Khoảng thời gian tối đa 62 ngày.');
        }
    }
}
