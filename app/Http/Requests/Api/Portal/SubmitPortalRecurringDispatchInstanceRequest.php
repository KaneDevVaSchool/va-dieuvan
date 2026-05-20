<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Portal\Concerns\ValidatesPortalRecurringExtracurricularInstance;
use App\Models\DispatchRequest;
use App\Support\Messages;
use Illuminate\Validation\Validator;

class SubmitPortalRecurringDispatchInstanceRequest extends ApiFormRequest
{
    use ValidatesPortalRecurringExtracurricularInstance;

    public function authorize(): bool
    {
        $user = $this->user();
        $dr = $this->route('dispatchRequest');

        return $user !== null
            && $dr instanceof DispatchRequest
            && ! $dr->trashed()
            && $user->can('submitStudentCount', $dr);
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
            if (! $dr instanceof DispatchRequest) {
                return;
            }

            $this->validatePortalRecurringInstanceBase($v, $dr);

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

            $this->validatePortalRecurringWithin24hOrSubmitted($v, $dr, 'depart_at');
        });
    }
}
