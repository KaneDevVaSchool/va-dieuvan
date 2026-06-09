<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Support\Messages;
use Illuminate\Validation\Validator;

class FillPriceDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.fill_price']);
    }

    /**
     * Trưởng đơn vị chỉ được gán khi tạo phiếu (portal); điều vận không gửi dept_head_user_id.
     */
    public function rules(): array
    {
        return [
            'service_price' => ['required', 'numeric', 'min:0'],
            'dept_head_user_id' => ['prohibited'],
            'rows' => ['nullable', 'array'],
            'rows.*.unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rows.*.extra_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rows.*.notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rows.*.transport_note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'rows.*.cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->requiresAssignedDeptHeadOnRequest()) {
                return;
            }

            $dr = $this->route('dispatchRequest');
            if ($dr instanceof DispatchRequest && $dr->assigned_dept_head_id === null) {
                $validator->errors()->add(
                    'assigned_dept_head_id',
                    Messages::REQUEST_DEPT_HEAD_MUST_BE_SET_ON_PORTAL,
                );
            }
        });
    }

    private function requiresAssignedDeptHeadOnRequest(): bool
    {
        if (! Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        if ($dr->trip_type === 'door_to_door') {
            return false;
        }

        return true;
    }
}
