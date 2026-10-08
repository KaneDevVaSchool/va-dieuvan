<?php

namespace App\Http\Requests\Api\Expenses;

use App\Models\ExpenseDocument;
use Illuminate\Validation\Rule;

class ExpenseCostCandidatesRequest extends DriverExpenseRequest
{
    public function rules(): array
    {
        return [
            'doc_type' => ['required', Rule::in([ExpenseDocument::TYPE_PAYMENT_REQUEST, ExpenseDocument::TYPE_NO_INVOICE_STATEMENT])],
            'vehicle_id' => ['nullable', 'integer'],
            'document_id' => ['nullable', 'integer'],
        ];
    }
}
