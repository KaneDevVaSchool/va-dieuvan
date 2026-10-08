<?php

namespace App\Http\Requests\Api\Checklist;

use App\Models\ChecklistSubmissionItem;

class SaveChecklistDraftRequest extends DriverChecklistRequest
{
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.id' => ['required', 'integer'],
            'items.*.result' => ['nullable', 'in:'.implode(',', ChecklistSubmissionItem::RESULTS)],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'inspector_name' => ['nullable', 'string', 'max:255'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'trip_info' => ['nullable', 'array'],
            'trip_info.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
