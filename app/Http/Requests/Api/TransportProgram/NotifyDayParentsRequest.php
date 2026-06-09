<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class NotifyDayParentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_attendance.manage']);
    }

    public function rules(): array
    {
        return [
            'shift' => ['nullable', 'string', 'in:morning,afternoon'],
            'scope' => ['nullable', 'string', 'in:selected,filtered,all_absent,not_marked'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'min:1'],
            'retry_batch_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
