<?php

namespace App\Http\Requests\Api\Expenses;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\ExpenseDocument;
use Illuminate\Validation\Rule;

/**
 * Back-office xem hồ sơ chi phí, sổ chi phí theo xe.
 */
class StaffExpenseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['expense.view', 'expense.manage']);
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['nullable', Rule::in(ExpenseDocument::TYPES)],
            'payment_kind' => ['nullable', Rule::in(ExpenseDocument::PAYMENT_KINDS)],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(ExpenseDocument::STATUSES)],
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
