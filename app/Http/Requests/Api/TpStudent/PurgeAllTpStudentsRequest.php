<?php

namespace App\Http\Requests\Api\TpStudent;

class PurgeAllTpStudentsRequest extends ListTpStudentsRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_student.manage']);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('permanent')) {
            $v = $this->input('permanent');
            if ($v === 'true' || $v === '1' || $v === 1 || $v === true) {
                $this->merge(['permanent' => true]);
            } elseif ($v === 'false' || $v === '0' || $v === 0 || $v === false) {
                $this->merge(['permanent' => false]);
            }
        }
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'permanent' => ['required', 'boolean'],
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
