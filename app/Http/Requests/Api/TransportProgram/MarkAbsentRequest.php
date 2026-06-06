<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class MarkAbsentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_attendance.manage']);
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:tp_students,id'],
            'absence_type' => ['required', 'in:absent,parent_notified,no_notice,late_cancel'],
            'category' => ['nullable', 'in:excused,unexcused'],
            'reason_code' => ['nullable', 'string', 'max:50', 'exists:tp_absence_reasons,code'],
            'absence_reason' => ['nullable', 'string', 'max:500'],
            'shift' => ['nullable', 'in:morning,afternoon'],
        ];
    }
}
