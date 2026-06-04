<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateSchoolCalendarRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['school_calendar.manage']);
    }

    public function rules(): array
    {
        return [
            'school_year' => ['sometimes', 'string', 'max:9'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
            'day_type' => ['sometimes', 'in:school_day,holiday,weekend,makeup_day'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
