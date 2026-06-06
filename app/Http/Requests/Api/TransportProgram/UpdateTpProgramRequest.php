<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTpProgramRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.manage']);
    }

    /**
     * Tài xế sơ cua không được trùng tài xế chính — so cả với giá trị hiện tại
     * khi payload chỉ gửi một trong hai trường.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('backup_driver_id') || $this->input('backup_driver_id') === null) {
                return;
            }
            $program = $this->route('tpProgram');
            $defaultId = $this->has('default_driver_id')
                ? $this->input('default_driver_id')
                : $program?->default_driver_id;

            if ($defaultId !== null && (int) $this->input('backup_driver_id') === (int) $defaultId) {
                $v->errors()->add('backup_driver_id', 'Tài xế sơ cua phải khác tài xế chính.');
            }
        });
    }

    public function rules(): array
    {
        $id = $this->route('tpProgram')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('tp_programs', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_name' => ['nullable', 'string', 'max:255'],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'departure_time' => ['sometimes', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'runs_on' => ['sometimes', 'array'],
            'runs_on.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'excluded_dates' => ['nullable', 'array'],
            'excluded_dates.*' => ['date'],
            'extra_dates' => ['nullable', 'array'],
            'extra_dates.*' => ['date'],
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'backup_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'default_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'cost_per_trip' => ['nullable', 'numeric', 'min:0'],
            'cost_notes' => ['nullable', 'string'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
