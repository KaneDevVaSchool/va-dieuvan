<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class MarkPresentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_attendance.manage']);
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['sometimes', 'array'],
            'student_ids.*' => ['integer', 'exists:tp_students,id'],
            'mark_all' => ['sometimes', 'boolean'],
        ];
    }
}
