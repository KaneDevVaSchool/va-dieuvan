<?php

namespace App\Http\Requests\Api\Checklist;

class ReviewChecklistRequest extends StaffChecklistRequest
{
    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
