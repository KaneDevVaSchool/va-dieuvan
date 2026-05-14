<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Support\Messages;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class UpdateRecurringDispatchRequestPassengerCountRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        /** @var mixed $dr */
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        return $user !== null && $user->can('adjustPassengerCount', $dr);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'passenger_count' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var mixed $dr */
            $dr = $this->route('dispatchRequest');

            if (! $dr instanceof DispatchRequest) {
                return;
            }

            if ($dr->trashed()) {
                $v->errors()->add('dispatchRequest', 'Phiếu không tồn tại.');
            }

            if ($dr->dispatch_request_template_id === null) {
                $v->errors()->add('dispatch_request_template_id', Messages::REQUEST_NOT_RECURRING_INSTANCE);
            }

            if (! is_string($dr->status) || ! in_array($dr->status, ['pending', 'price_filled'], true)) {
                $v->errors()->add(
                    'passenger_count',
                    'Chỉ cập nhật khi phiếu đang chờ điều xử lý hoặc đã điền giá (chưa duyệt).'
                );
            }

            if ($dr->depart_at === null) {
                return;
            }

            try {
                $departAt = Carbon::parse($dr->depart_at);
            } catch (\Throwable) {
                return;
            }

            if (! $departAt->greaterThan(now()->copy()->addHours(24))) {
                $v->errors()->add('passenger_count', Messages::REQUEST_RECURRING_PASSENGER_COUNT_LOCKED);
            }
        });
    }
}
