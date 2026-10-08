<?php

namespace App\Http\Requests\Api\Checklist;

class SubmitChecklistRequest extends SaveChecklistDraftRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'confirm' => ['accepted'],
            'client_submitted_at' => ['nullable', 'date'],
        ]);
    }

    public function messages(): array
    {
        return [
            'confirm.accepted' => 'Vui lòng xác nhận lời cam kết trước khi nộp.',
        ];
    }
}
