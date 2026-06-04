<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class BulkSchoolCalendarRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['school_calendar.manage']);
    }

    public function rules(): array
    {
        return [
            'school_year' => ['required', 'string', 'max:9'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.day_type' => ['required', 'in:school_day,holiday,weekend,makeup_day'],
            'entries.*.semester' => ['nullable', 'integer', 'in:1,2'],
            'entries.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
