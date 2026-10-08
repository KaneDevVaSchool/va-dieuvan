<?php

namespace App\Http\Requests\Api\Expenses;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Kế toán xử lý hồ sơ (trả lại, đã thanh toán, hủy) và cấu hình mẫu in. Luật validate chọn theo route action.
 */
class ManageExpenseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('GET')) {
            return $this->allowAnyOf(['expense.view', 'expense.manage']);
        }

        return $this->allowAnyOf(['expense.manage']);
    }

    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'reject' => ['reason' => ['required', 'string', 'max:255']],
            'markPaid' => ['paid_at' => ['nullable', 'date']],
            'updateSettings' => [
                'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'tax_code' => ['sometimes', 'nullable', 'string', 'max:32'],
                'company_address' => ['sometimes', 'nullable', 'string', 'max:255'],
                'purchasing_place' => ['sometimes', 'nullable', 'string', 'max:255'],
                'city_label' => ['sometimes', 'nullable', 'string', 'max:255'],
                'default_department_label' => ['sometimes', 'nullable', 'string', 'max:255'],
                'form_codes' => ['sometimes', 'array'],
                'form_codes.*.code' => ['nullable', 'string', 'max:64'],
                'form_codes.*.issued_on' => ['nullable', 'string', 'max:32'],
                'form_codes.*.revision' => ['nullable', 'string', 'max:16'],
                'default_signatories' => ['sometimes', 'array'],
                'default_signatories.*' => ['array', 'max:8'],
                'default_signatories.*.*.role' => ['required', 'string', 'max:120'],
                'default_signatories.*.*.name' => ['nullable', 'string', 'max:120'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return ['reason.required' => 'Vui lòng nhập lý do trả lại.'];
    }
}
