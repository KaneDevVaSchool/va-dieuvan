<?php

namespace App\Http\Requests\Api\Checklist;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\ChecklistTemplateItem;
use Illuminate\Validation\Rule;

/**
 * Cấu hình checklist (template, mục, khung giờ nhắc). Luật validate chọn theo route action.
 */
class ManageChecklistSettingsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('GET')) {
            return $this->allowAnyOf(['checklist.manage', 'checklist.view']);
        }

        return $this->allowAnyOf(['checklist.manage']);
    }

    public function rules(): array
    {
        $action = $this->route()?->getActionMethod();
        $partial = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return match ($action) {
            'updateTemplate' => [
                'name' => ['sometimes', 'string', 'max:255'],
                'is_active' => ['sometimes', 'boolean'],
                'form_meta' => ['sometimes', 'array'],
                'form_meta.*' => ['nullable', 'string', 'max:500'],
            ],
            'storeItem', 'updateItem' => [
                'group_name' => [$partial, 'string', 'max:120'],
                'title' => [$partial, 'string', 'max:255'],
                'requirement' => ['nullable', 'string', 'max:1000'],
                'severity' => [$partial, Rule::in(ChecklistTemplateItem::SEVERITIES)],
                'ng_action' => ['nullable', 'string', 'max:1000'],
                'allow_na' => ['sometimes', 'boolean'],
                'requires_photo_on_ng' => ['sometimes', 'boolean'],
                'auto_source' => ['nullable', 'string', 'max:64', 'regex:/^(driver_doc|vehicle_doc):[a-z_]+$/'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ],
            'reorderItems' => [
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer'],
            ],
            'storeSlot', 'updateSlot' => [
                'template_id' => [$partial, 'integer', 'exists:checklist_templates,id'],
                'remind_at' => [$partial, 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
                'weekdays' => [$partial, 'array', 'min:1'],
                'weekdays.*' => ['integer', 'between:1,7'],
                'escalate_after_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'remind_at.regex' => 'Giờ nhắc phải có dạng HH:MM.',
            'weekdays.min' => 'Chọn ít nhất một ngày trong tuần.',
        ];
    }
}
