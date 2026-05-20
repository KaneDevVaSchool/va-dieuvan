<?php

namespace App\Http\Requests\Api\Portal;

use App\Models\DispatchRequestTemplate;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePortalDispatchPlanLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $template = $this->route('dispatchRequestTemplate');
        if (! $template instanceof DispatchRequestTemplate) {
            return false;
        }

        return (int) $template->requester_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_label' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
