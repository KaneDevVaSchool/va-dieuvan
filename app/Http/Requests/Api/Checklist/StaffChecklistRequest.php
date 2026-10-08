<?php

namespace App\Http\Requests\Api\Checklist;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Điều phối xem checklist: danh sách, chi tiết, file in, ma trận tuân thủ.
 */
class StaffChecklistRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['checklist.view']);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,submitted,reviewed'],
            'result' => ['nullable', 'in:pass,fail'],
            'frequency' => ['nullable', 'in:daily,weekly'],
            'driver_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
