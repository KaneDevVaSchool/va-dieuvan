<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class GenerateSchoolCalendarMonthRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['school_calendar.manage']);
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'school_year' => ['required', 'string', 'max:9'],
            'semester' => ['required', 'integer', 'in:1,2'],
        ];
    }
}
