<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Validator;

class UpdateUserProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:4096'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['phone', 'avatar']) as $field) {
                $validator->errors()->add($field, 'Thông tin nhân sự được quản lý qua HCNS; chỉ có thể cập nhật điện thoại và ảnh đại diện tại đây.');
            }
        });
    }
}
