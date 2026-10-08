<?php

namespace App\Http\Requests\Api\Expenses;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\ExpenseDocument;
use Illuminate\Validation\Rule;

/**
 * Người lập hồ sơ (tài xế) — đọc danh sách / chi tiết / file in của chính mình.
 */
class DriverExpenseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['expense.submit']);
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['nullable', Rule::in(ExpenseDocument::TYPES)],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(ExpenseDocument::STATUSES)],
            'vehicle_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
