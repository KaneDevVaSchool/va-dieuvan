<?php

namespace App\Http\Requests\Api\Expenses;

class SubmitExpenseDocumentRequest extends DriverExpenseRequest
{
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
