<?php

namespace App\Services\SignedDocuments;

use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SigningWorkflowService
{
    private const ALLOWED = [
        'awaiting_signature',
        'signing_in_progress',
        'signing_complete',
        'signed_uploaded',
    ];

    public function updateStatus(DispatchRequest $dispatchRequest, User $user, string $status): DispatchRequest
    {
        if ($dispatchRequest->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => ['Chỉ cập nhật trạng thái ký khi phiếu đã duyệt.'],
            ]);
        }

        if ((int) $dispatchRequest->requester_id !== (int) $user->id) {
            abort(403);
        }

        if (! in_array($status, self::ALLOWED, true)) {
            throw ValidationException::withMessages([
                'status' => ['Trạng thái không hợp lệ.'],
            ]);
        }

        $dispatchRequest->forceFill(['signing_workflow_status' => $status])->save();

        return $dispatchRequest->fresh();
    }
}
