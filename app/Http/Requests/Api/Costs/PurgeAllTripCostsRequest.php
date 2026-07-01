<?php

namespace App\Http\Requests\Api\Costs;

class PurgeAllTripCostsRequest extends ListAllTripCostsRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.cost.reconcile']);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'confirm_phrase' => ['required', 'string', 'max:64'],
            'expected_count' => ['required', 'integer', 'min:0', 'max:500000'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $expected = (int) $this->input('expected_count');
            $phrase = trim((string) $this->input('confirm_phrase'));
            $required = 'XOA '.$expected;
            if ($phrase !== $required) {
                $validator->errors()->add(
                    'confirm_phrase',
                    'Nhập đúng cụm xác nhận: «'.$required.'» (in hoa, có dấu cách trước số).',
                );
            }
        });
    }
}
