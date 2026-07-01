<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
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

        // Người đề xuất đính kèm bản scan phiếu đã ký (BM.03) sau khi Trưởng đơn vị duyệt — không yêu cầu attachment.upload.
        if (
            $this->input('attachable_type') === 'dispatch_request'
            && $this->input('kind') === 'signed_paper'
        ) {
            $id = (int) $this->input('attachable_id');
            if ($id < 1) {
                return false;
            }

            $dr = DispatchRequest::query()->find($id);
            if ($dr === null || $dr->status !== 'approved') {
                return false;
            }

            return (int) $dr->requester_id === (int) $user->id;
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
        if ($cost->trip !== null) {
            return TripVisibility::userCanViewTrip($user, $cost->trip);
        }

        return (int) $cost->created_by === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', Rule::in(['trip', 'cargo_shipment', 'trip_cost', 'dispatch_request', 'driver_compliance_document', 'vehicle_compliance_document'])],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', 'max:50', Rule::in(['paper_scan', 'request_attachment', 'proposal_basis', 'signed_paper', 'receipt', 'pod', 'route_doc'])],
            // Whitelist loại tệp: ảnh + tài liệu công vụ. Chặn html/svg/js/php… (Stored XSS / khả năng RCE).
            // `mimes` kiểm tra theo nội dung, `extensions` kiểm tra đuôi do client gửi — dùng cả hai.
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,webp,gif,heic,heif,pdf,xlsx,xls,docx,doc,csv',
                'extensions:jpg,jpeg,png,webp,gif,heic,heif,pdf,xlsx,xls,docx,doc,csv',
            ],
        ];
    }
}
