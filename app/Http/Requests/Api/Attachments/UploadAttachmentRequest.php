<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\TripCost;
use App\Models\User;
use App\Support\TripVisibility;
use Illuminate\Validation\Rule;

class UploadAttachmentRequest extends ApiFormRequest
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

        // Tài xế thường có trip.record.create / trip.update_status nhưng có thể chưa được gán attachment.upload trên production.
        if ($this->input('attachable_type') !== 'trip_cost') {
            return false;
        }

        if (! $this->allowAnyOf(['trip.record.create', 'trip.update_status'])) {
            return false;
        }

        $id = (int) $this->input('attachable_id');
        if ($id < 1) {
            return false;
        }

        $cost = TripCost::query()->find($id);
        if (! $cost) {
            return false;
        }

        $cost->loadMissing('trip');
        if (! $cost->trip) {
            return false;
        }

        return TripVisibility::userCanViewTrip($user, $cost->trip);
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', Rule::in(['trip', 'cargo_shipment', 'trip_cost', 'dispatch_request', 'driver_compliance_document', 'vehicle_compliance_document'])],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', 'max:50', Rule::in(['paper_scan', 'request_attachment', 'proposal_basis', 'signed_paper', 'receipt', 'pod', 'route_doc'])],
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ];
    }
}
