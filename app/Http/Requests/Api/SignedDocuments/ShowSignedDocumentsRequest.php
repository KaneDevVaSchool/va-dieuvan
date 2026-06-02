<?php

namespace App\Http\Requests\Api\SignedDocuments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\User;

class ShowSignedDocumentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            return false;
        }

        if ((int) $dr->requester_id === (int) $user->id) {
            return true;
        }

        return $user->can('view', $dr);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'include_history' => ['sometimes', 'boolean'],
        ];
    }
}
