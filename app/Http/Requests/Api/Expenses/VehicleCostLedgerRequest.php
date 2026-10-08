<?php

namespace App\Http\Requests\Api\Expenses;

class VehicleCostLedgerRequest extends StaffExpenseRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ];
    }
}
