<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Resources\SignedDocumentVersionResource;
use App\Models\DispatchRequest;
use App\Models\SignedDocumentVersion;

trait PresentsSignedDocuments
{
    /**
     * @return array<string, mixed>
     */
    protected function presentSignedDocumentBlock(DispatchRequest $dispatchRequest, bool $includeHistory = false): array
    {
        $current = $dispatchRequest->relationLoaded('currentSignedVersion')
            ? $dispatchRequest->currentSignedVersion
            : null;

        if ($current === null && $dispatchRequest->current_signed_version_id) {
            $current = SignedDocumentVersion::query()
                ->with(['attachment', 'uploader'])
                ->find($dispatchRequest->current_signed_version_id);
        }

        $versions = [];
        if ($includeHistory) {
            $list = $dispatchRequest->relationLoaded('signedDocumentVersions')
                ? $dispatchRequest->signedDocumentVersions
                : SignedDocumentVersion::query()
                    ->where('dispatch_request_id', $dispatchRequest->getKey())
                    ->with(['attachment', 'uploader'])
                    ->orderByDesc('version_no')
                    ->get();

            $versions = SignedDocumentVersionResource::collection($list)->resolve();
        }

        return [
            'signing_workflow_status' => $dispatchRequest->signing_workflow_status,
            'signed_document' => [
                'current' => $current
                    ? (new SignedDocumentVersionResource($current))->resolve()
                    : null,
                'versions' => $includeHistory ? $versions : [],
            ],
        ];
    }
}
