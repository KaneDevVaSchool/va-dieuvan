<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

class CreateMaintenanceReminderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u && $u->canAccessDriverWebApp();
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'repeat_type' => ['required', 'in:once,weekly,monthly'],
            'remind_at'   => ['required', 'date', 'after:now'],
        ];
    }
}
