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
     * Gán Trưởng đơn vị bắt buộc khi có phòng của người đề xuất và trong hệ thống có role department_head (quy trình duyệt BP).
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
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        if (! Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            return false;
        }

        $dr->loadMissing('requester:id,department_id');

        return $dr->requester?->department_id !== null;
    }
}
