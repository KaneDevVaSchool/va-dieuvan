<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

/**
 * Thêm tay người dùng không có trong CMS (để phân quyền). Đăng nhập bằng Google với đúng email này.
 */
class StoreUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'employee_code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'email' => 'email',
            'employee_code' => 'mã nhân viên',
            'phone' => 'số điện thoại',
            'role_id' => 'vai trò',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã có trong hệ thống — tìm người dùng trong danh sách để gán vai trò.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'employee_code' => is_string($this->employee_code) ? (trim($this->employee_code) ?: null) : $this->employee_code,
            'phone' => is_string($this->phone) ? (trim($this->phone) ?: null) : $this->phone,
        ]);
    }
}
