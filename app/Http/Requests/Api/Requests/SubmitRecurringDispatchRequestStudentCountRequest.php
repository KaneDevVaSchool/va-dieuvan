<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Support\Messages;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class SubmitRecurringDispatchRequestStudentCountRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        /** @var mixed $dr */
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        return $user !== null && $user->can('submitStudentCount', $dr);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var mixed $dr */
            $dr = $this->route('dispatchRequest');
            $user = $this->user();

            if (! $dr instanceof DispatchRequest) {
                return;
            }

            if ($dr->trashed()) {
                $v->errors()->add('dispatchRequest', 'Phiếu không tồn tại.');
            }

            if ($dr->dispatch_request_template_id === null) {
                $v->errors()->add('dispatch_request_template_id', Messages::REQUEST_NOT_RECURRING_INSTANCE);
            }

            if ($dr->student_count_submitted_at !== null) {
                $v->errors()->add('student_count_submitted_at', Messages::REQUEST_RECURRING_STUDENT_COUNT_ALREADY_SUBMITTED);
            }

            if ($dr->student_count_actual === null) {
                $v->errors()->add('student_count_actual', Messages::REQUEST_RECURRING_STUDENT_COUNT_NOT_SAVED);
            }

            $count = (int) $dr->student_count_actual;
            if ($count < 1 || $count > 999) {
                $v->errors()->add('student_count_actual', 'Số học sinh phải từ 1 đến 999.');
            }

            if (in_array((string) $dr->status, ['rejected'], true)) {
                $v->errors()->add('status', 'Không thể gửi chốt cho phiếu đã từ chối.');
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
                $v->errors()->add('depart_at', Messages::REQUEST_RECURRING_STUDENT_COUNT_SUBMIT_LOCKED);
            }
        });
    }
}
