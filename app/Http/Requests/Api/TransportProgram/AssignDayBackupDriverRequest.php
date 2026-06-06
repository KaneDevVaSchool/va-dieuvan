<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class AssignDayBackupDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_driver_assign.manage']);
    }

    public function rules(): array
    {
        return [
            'backup_driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'shift' => ['nullable', 'in:morning,afternoon'],
        ];
    }
}
