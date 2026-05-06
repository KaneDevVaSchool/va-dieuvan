<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;

/** Cài đặt ngưỡng gấp — đọc để hiển thị form tạo yêu cầu. */
class ReadDispatchFormSettingsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.create']);
    }

    public function rules(): array
    {
        return [];
    }
}
