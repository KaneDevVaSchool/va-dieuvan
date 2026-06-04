<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

/** Dùng cho các hành động không có body: xoá mềm, xem số chuyến bị ảnh hưởng. */
class ManageStudentPolicyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
