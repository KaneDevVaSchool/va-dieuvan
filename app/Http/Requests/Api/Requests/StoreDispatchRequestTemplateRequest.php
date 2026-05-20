<?php

namespace App\Http\Requests\Api\Requests;

use App\Support\Messages;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDispatchRequestTemplateRequest extends CreateDispatchRequestRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'recurrence_rule' => ['required', 'array'],
            'recurrence_rule.freq' => ['required', 'string', Rule::in(['weekly', 'daily'])],
            'recurrence_rule.interval' => ['nullable', 'integer', 'min:1', 'max:52'],
            'recurrence_rule.byweekday' => ['nullable', 'array'],
            'recurrence_rule.byweekday.*' => ['integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'recurrence_end_date' => ['nullable', 'date', 'required_without:repeat_count'],
            'repeat_count' => ['nullable', 'integer', 'min:1', 'max:520', 'required_without:recurrence_end_date'],
            'start_date' => ['required', 'date'],
            'return_time' => ['required', 'date_format:H:i'],
            'dispatch_package_id' => ['nullable', 'integer', 'exists:dispatch_packages,id'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $v): void {
            if ($this->input('trip_type') !== 'point_to_point') {
                $v->errors()->add('trip_type', Messages::REQUEST_RECURRING_TEMPLATE_POINT_TO_POINT_ONLY);
            }

            $snap = $this->input('wizard_snapshot');
            if (! is_array($snap) || (($snap['point_purpose_kind'] ?? null) !== 'extracurricular')) {
                $v->errors()->add('wizard_snapshot', Messages::REQUEST_RECURRING_TEMPLATE_EXTRACURRICULAR_ONLY);
            }

            $freq = strtolower((string) data_get($this->input('recurrence_rule'), 'freq', ''));
            if ($freq === 'weekly') {
                $days = data_get($this->input('recurrence_rule'), 'byweekday');
                if (! is_array($days) || $days === []) {
                    $v->errors()->add('recurrence_rule.byweekday', __('validation.required', ['attribute' => 'recurrence_rule.byweekday']));
                }
            }
        });
    }
}
