<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Attachment;
use App\Models\TripCost;
use App\Models\User;
use App\Support\TripVisibility;

class DestroyAttachmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->can('attachment.upload')) {
            return true;
        }

        $attachment = $this->route('attachment');
        if (! $attachment instanceof Attachment) {
            return false;
        }

        $parent = $attachment->attachable;
        if (! $parent instanceof TripCost) {
            return false;
        }

        if (! $this->allowAnyOf(['trip.record.create', 'trip.update_status'])) {
            return false;
        }

        $parent->loadMissing('trip');
        if ($parent->trip !== null) {
            return TripVisibility::userCanViewTrip($user, $parent->trip);
        }

        return (int) $parent->created_by === (int) $user->id;
    }

    public function rules(): array
    {
        return [];
    }
}
