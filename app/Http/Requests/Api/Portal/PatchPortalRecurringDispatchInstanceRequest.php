<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Portal\Concerns\ValidatesPortalRecurringExtracurricularInstance;
use App\Models\DispatchRequest;
use App\Support\DispatchRequestDeptHeadAssignment;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class PatchPortalRecurringDispatchInstanceRequest extends ApiFormRequest
{
    use ValidatesPortalRecurringExtracurricularInstance;

    public function authorize(): bool
    {
        $user = $this->user();
        $dr = $this->route('dispatchRequest');

        return $user !== null
            && $dr instanceof DispatchRequest
            && ! $dr->trashed()
            && $user->can('adjustPassengerCount', $dr);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_count_actual' => ['nullable', 'integer', 'min:1', 'max:999'],
            'depart_at' => ['nullable', 'date'],
            'arrive_by' => ['nullable', 'date', 'after_or_equal:depart_at'],
            'origin' => ['nullable', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'wizard_snapshot' => ['nullable', 'array'],
            'dept_head_user_id' => ['nullable', 'integer', 'exists:users,id'],
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

            $this->validatePortalRecurringInstanceBase($v, $dr);
            $this->validatePortalRecurringWithin24hOrSubmitted($v, $dr);

            if (! in_array((string) $dr->status, ['pending', 'price_filled'], true)) {
                $v->errors()->add('status', 'Không thể chỉnh sửa phiếu ở trạng thái hiện tại.');
            }

            $hasField = $this->filled('student_count_actual')
                || $this->filled('depart_at')
                || $this->filled('arrive_by')
                || $this->filled('origin')
                || $this->filled('destination')
                || $this->has('notes')
                || $this->filled('wizard_snapshot')
                || $this->filled('dept_head_user_id');

            if (! $hasField) {
                $v->errors()->add('payload', 'Cần ít nhất một trường cập nhật.');
            }

            if ($this->filled('dept_head_user_id')
                && DispatchRequestDeptHeadAssignment::requiresChoice((string) $dr->trip_type)) {
                DispatchRequestDeptHeadAssignment::resolveValidatedId($this->input('dept_head_user_id'));
            }

            if ($this->filled('depart_at')) {
                try {
                    $newDepart = Carbon::parse($this->input('depart_at'));
                } catch (\Throwable) {
                    return;
                }
                if (! $newDepart->greaterThan(now()->copy()->addHours(24))) {
                    $v->errors()->add('depart_at', 'Giờ khởi hành phải sau ít nhất 24 giờ so với hiện tại.');
                }
            }
        });
    }
}
