<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class AddTripEventRequest extends ApiFormRequest
{
    /** Loại sự kiện hợp lệ do client gửi (status_change do server tự tạo). */
    public const ALLOWED_TYPES = ['passenger_pickup', 'note'];

    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.event.create']);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(self::ALLOWED_TYPES)],
            'message' => ['nullable', 'string', 'max:2000'],
            'data' => ['nullable', 'array'],
            'data.row_index' => ['sometimes', 'integer', 'min:0'],
            'data.state' => ['sometimes', 'string', Rule::in(['picked_up', 'absent'])],
            'data.at' => ['sometimes', 'string', 'max:40'],
        ];
    }
}
