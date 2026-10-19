<?php

namespace App\Http\Requests\Api\Expenses;

use App\Models\ExpenseDocument;
use Illuminate\Validation\Rule;

/**
 * Tạo (POST) / sửa (PATCH) hồ sơ chi phí.
 */
class SaveExpenseDocumentRequest extends DriverExpenseRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'lock_version' => [$creating ? 'nullable' : 'required', 'integer', 'min:0'],
            'doc_type' => [$creating ? 'required' : 'prohibited', Rule::in(ExpenseDocument::TYPES)],
            'payment_kind' => [$creating ? 'nullable' : 'prohibited', Rule::in(ExpenseDocument::PAYMENT_KINDS)],
            'parent_id' => [$creating ? 'nullable' : 'prohibited', 'integer'],

            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['nullable', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:32'],
            'department_label' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'basis' => ['nullable', 'string', 'max:2000'],
            'proposed_on' => ['nullable', 'date'],
            'needed_by' => ['nullable', 'date'],
            'is_urgent' => ['nullable', 'boolean'],
            'urgent_reason' => ['nullable', 'string', 'max:255'],
            'allocation_target' => ['nullable', 'string', 'max:255'],
            'allocation_area' => ['nullable', 'string', 'max:255'],
            'handover_person' => ['nullable', 'string', 'max:255'],
            'purchasing_person' => ['nullable', 'string', 'max:255'],
            'payment_due_label' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(['cash', 'transfer'])],
            'bank_account_no' => ['nullable', 'string', 'max:64'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],

            'signatories' => ['nullable', 'array', 'max:8'],
            'signatories.*.role' => ['required', 'string', 'max:120'],
            'signatories.*.name' => ['nullable', 'string', 'max:120'],

            'lines' => ['sometimes', 'array', 'max:100'],
            'lines.*.id' => ['nullable', 'integer', 'distinct'],
            'lines.*.vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'lines.*.trip_cost_id' => ['nullable', 'integer', 'distinct'],
            'lines.*.cost_type' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/i'],
            'lines.*.category' => ['nullable', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.unit' => ['nullable', 'string', 'max:32'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'lines.*.purchased_on' => ['nullable', 'date'],
            'lines.*.seller_name' => ['nullable', 'string', 'max:255'],
            'lines.*.seller_address' => ['nullable', 'string', 'max:255'],
            'lines.*.has_invoice' => ['nullable', 'boolean'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
