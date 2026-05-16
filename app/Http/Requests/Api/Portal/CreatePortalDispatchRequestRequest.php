<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Chỉ user đăng nhập nhưng chưa có quyền staff/driver — không kiểm tra permission.request.create.
 */
class CreatePortalDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ! $user->canAccessDispatchWebApp()
            && ! $user->canAccessDriverWebApp();
    }

    public function rules(): array
    {
        return [
            'trip_type' => ['required', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'depart_at' => ['required', 'date'],
            'arrive_by' => ['nullable', 'date', 'after_or_equal:depart_at'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string'],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'is_urgent' => ['nullable', 'boolean'],
            'urgent_reason' => ['nullable', 'string', 'max:500'],
            'wizard_snapshot' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $departRaw = $this->input('depart_at');
            $tripType = (string) $this->input('trip_type', '');
            if (! $departRaw || $tripType === '') {
                return;
            }
            try {
                $depart = Carbon::parse($departRaw);
            } catch (\Throwable) {
                return;
            }
            [$mustUrgent] = DispatchRequest::resolveUrgentTrigger(
                $tripType,
                $depart,
                $this->boolean('is_urgent'),
            );
            if ($mustUrgent && ! $this->filled('urgent_reason')) {
                $v->errors()->add('urgent_reason', __('validation.required', ['attribute' => 'urgent_reason']));
            }
        });
    }
}
