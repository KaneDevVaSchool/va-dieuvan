<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Support\Messages;
use Illuminate\Validation\Rule;

class FillPriceDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.fill_price']);
    }

    /**
     * Bắt buộc chọn một Trưởng BP khi hệ thống có role `department_head` (luồng fill giá).
     */
    public function rules(): array
    {
        return [
            'service_price' => ['required', 'numeric', 'min:0'],
            'dept_head_user_id' => [
                Rule::requiredIf(fn () => $this->requiresDeptHeadUserId()),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'rows' => ['nullable', 'array'],
            'rows.*.unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rows.*.extra_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rows.*.notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rows.*.transport_note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'rows.*.cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'dept_head_user_id.required' => Messages::REQUEST_DEPT_HEAD_REQUIRED,
        ];
    }

    private function requiresDeptHeadUserId(): bool
    {
        if (! Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if ($dr instanceof DispatchRequest && $dr->assigned_dept_head_id !== null) {
            return false;
        }

        return true;
    }
}
