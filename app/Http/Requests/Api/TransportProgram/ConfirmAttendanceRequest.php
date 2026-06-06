<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class ConfirmAttendanceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_attendance.manage', 'tp_attendance.confirm']);
    }

    public function rules(): array
    {
        return [
            'attendance_lock_version' => ['required', 'integer', 'min:0'],
            'shift' => ['nullable', 'in:morning,afternoon'],
        ];
    }
}
