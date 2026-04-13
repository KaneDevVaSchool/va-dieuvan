<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    public function wantsJson(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Phân quyền theo tên permission — tạm tắt; chỉ cần đăng nhập.
     * Bật lại bằng cách khôi phục kiểm tra `hasPermission` trong các nhánh bên dưới.
     */
    protected function allowAnyOf(array $permissions): bool
    {
        return (bool) $this->user();
    }

    protected function allowAllOf(array $permissions): bool
    {
        return (bool) $this->user();
    }
}
