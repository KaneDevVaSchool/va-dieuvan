<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class DriverTripHistoryRequest extends ApiFormRequest
{
    /** Giới hạn cửa sổ ngày (dashboard tài xế ~51 ngày). */
    private const MAX_DATE_SPAN_DAYS = 60;

    public function authorize(): bool
    {
        $u = $this->user();

        return $u && $u->canAccessDriverWebApp();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('status') && $this->input('status') === '') {
            $merge['status'] = null;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => [
                'required',
                'date',
                'after_or_equal:date_from',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $from = $this->input('date_from');
                    if ($from === null || $from === '') {
                        return;
                    }
                    try {
                        $start = Carbon::parse((string) $from)->startOfDay();
                        $end = Carbon::parse((string) $value)->startOfDay();
                        if ($start->diffInDays($end) > self::MAX_DATE_SPAN_DAYS) {
                            $fail(sprintf(
                                'Khoảng ngày từ date_from đến date_to không được vượt quá %d ngày.',
                                self::MAX_DATE_SPAN_DAYS
                            ));
                        }
                    } catch (\Throwable) {
                        // other rules handle invalid dates
                    }
                },
            ],
            'status' => ['nullable', 'string', Rule::in(['completed', 'cancelled', 'pending', 'in_progress'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
