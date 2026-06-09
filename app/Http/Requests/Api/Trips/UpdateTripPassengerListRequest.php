<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateTripPassengerListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        if ($this->has('passengers')) {
            return [
                'passenger_count' => ['required', 'integer', 'min:1', 'max:50'],
                'passengers' => ['required', 'array'],
                'passengers.*.name' => ['required', 'string', 'max:255'],
                'passengers.*.phone' => ['nullable', 'string', 'max:20'],
                'passengers.*.note' => ['nullable', 'string', 'max:2000'],
                'lock_version' => ['required', 'integer', 'min:0'],
            ];
        }

        return [
            'passenger_rows' => ['sometimes', 'array', 'max:50'],
            'business_rows' => ['sometimes', 'array', 'max:50'],
            'cargo_rows' => ['sometimes', 'array', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $this->all();
            $hasNamed = isset($data['passengers']);

            $hasLegacyPayload = isset($data['passenger_rows'])
                || isset($data['business_rows'])
                || isset($data['cargo_rows']);

            if ($hasNamed && $hasLegacyPayload) {
                $v->errors()->add('passengers', 'Không được gửi passengers cùng passenger_rows/business_rows/cargo_rows.');
            }

            if (! $hasNamed) {
                return;
            }

            $n = (int) ($data['passenger_count'] ?? 0);
            /** @var array<int, mixed> $list */
            $list = is_array($data['passengers']) ? $data['passengers'] : [];
            if (count($list) !== $n) {
                $v->errors()->add('passengers', 'Danh sách hành khách phải có đủ số phần tử bằng passenger_count.');
            }
        });
    }
}
