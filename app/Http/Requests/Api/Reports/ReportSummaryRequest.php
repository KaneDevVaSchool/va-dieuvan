<?php

namespace App\Http\Requests\Api\Reports;

use App\Http\Requests\Api\ApiFormRequest;

class ReportSummaryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['report.view']);
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
