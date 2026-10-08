<?php

namespace App\Http\Requests\Api\Checklist;

class StartChecklistRequest extends DriverChecklistRequest
{
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:checklist_templates,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'trip_id' => ['nullable', 'integer', 'min:1'],
            'tp_program_day_id' => ['nullable', 'integer', 'min:1'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Vui lòng chọn xe kiểm tra.',
            'template_id.required' => 'Vui lòng chọn loại checklist.',
        ];
    }
}
