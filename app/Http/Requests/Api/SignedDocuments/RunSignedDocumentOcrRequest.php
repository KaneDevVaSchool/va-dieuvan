<?php

namespace App\Http\Requests\Api\SignedDocuments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\SignedDocumentVersion;
use App\Models\User;

class RunSignedDocumentOcrRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->can('request.paper.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function prepareForValidation(): void
    {
        $version = $this->route('signedDocumentVersion');
        if ($version instanceof SignedDocumentVersion && $version->dispatchRequest?->trashed()) {
            abort(404);
        }
    }
}
