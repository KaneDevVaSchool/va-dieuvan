<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Trip;
use App\Services\Dispatching\TripScheduleLegService;
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
                'passengers.*.leg_key' => ['nullable', 'string', 'max:64'],
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

            $this->validateLegKeys($v, $list);
        });
    }

    /**
     * Mỗi hành khách có thể gán vào một chặng (leg_key) hợp lệ của chuyến.
     *
     * @param  array<int, mixed>  $list
     */
    private function validateLegKeys(Validator $v, array $list): void
    {
        $sent = [];
        foreach ($list as $i => $row) {
            $key = is_array($row) ? trim((string) ($row['leg_key'] ?? '')) : '';
            if ($key !== '') {
                $sent[$i] = $key;
            }
        }

        if ($sent === []) {
            return;
        }

        $trip = $this->route('trip');
        if (! $trip instanceof Trip) {
            return;
        }

        $validKeys = [];
        foreach (app(TripScheduleLegService::class)->resolveScheduleLegsForTrip($trip) as $leg) {
            $k = (string) ($leg['key'] ?? '');
            if ($k !== '') {
                $validKeys[$k] = true;
            }
        }

        foreach ($sent as $i => $key) {
            if (! isset($validKeys[$key])) {
                $v->errors()->add("passengers.$i.leg_key", 'Chặng được gán không hợp lệ.');
            }
        }
    }
}
