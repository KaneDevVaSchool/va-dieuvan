<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Support\Messages;

class CloneDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->can('request.create')) {
            return false;
        }

        $dr = $this->route('dispatchRequest');

        return $dr instanceof DispatchRequest && $user->can('view', $dr);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    protected function passedValidation(): void
    {
        /** @var mixed $dr */
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return;
        }

        if (! is_string($dr->status) || ! in_array($dr->status, ['approved', 'rejected'], true)) {
            abort(422, Messages::REQUEST_CLONE_INVALID_STATUS);
        }
    }
}
