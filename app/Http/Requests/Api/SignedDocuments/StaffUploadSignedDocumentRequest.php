<?php

namespace App\Http\Requests\Api\SignedDocuments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\User;

class StaffUploadSignedDocumentRequest extends ApiFormRequest
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

        if ($user->can('request.paper.manage')) {
            return true;
        }

        if ($dr->status !== 'approved') {
            return false;
        }

        return (int) $dr->requester_id === (int) $user->id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = max(1, (int) config('dispatch.signed_upload.max_mb', 10)) * 1024;

        return [
            'file' => ['required', 'file', 'max:'.$maxKb],
        ];
    }
}
