<?php

namespace App\Http\Requests\Api\Requests;

use App\Models\DispatchRequest;

class UpdateDispatchRequestWizardRequest extends CreateDispatchRequestRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('request.create')) {
            return false;
        }

        /** @var mixed $dr */
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            return false;
        }

        if ($dr->status !== 'pending') {
            return false;
        }

        return $user->can('view', $dr);
    }
}
