<?php

namespace App\Http\Requests\Api\Checklist;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\ChecklistSubmission;

/**
 * Đọc checklist phía tài xế (tổng quan, lịch sử, chi tiết, file in).
 */
class DriverChecklistRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->canAccessDriverWebApp() && $this->allowAnyOf(['checklist.submit']);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', [ChecklistSubmission::STATUS_DRAFT, ChecklistSubmission::STATUS_SUBMITTED, ChecklistSubmission::STATUS_REVIEWED])],
            'frequency' => ['nullable', 'in:daily,weekly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
