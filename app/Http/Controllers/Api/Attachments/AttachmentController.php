<?php

namespace App\Http\Controllers\Api\Attachments;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attachments\RunAttachmentOcrRequest;
use App\Http\Requests\Api\Attachments\UploadAttachmentRequest;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\DriverComplianceDocument;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\VehicleComplianceDocument;
use App\Services\Auditing\AuditLogger;
use App\Services\Ocr\PaperOcrStubService;
use App\Support\FinancialDataLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    use ApiResponses;

    public function upload(UploadAttachmentRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        [$attachable, $folder] = match ($data['attachable_type']) {
            'trip' => [Trip::findOrFail($data['attachable_id']), 'trips'],
            'cargo_shipment' => [CargoShipment::findOrFail($data['attachable_id']), 'cargo'],
            'trip_cost' => [TripCost::findOrFail($data['attachable_id']), 'costs'],
            'dispatch_request' => [DispatchRequest::findOrFail($data['attachable_id']), 'dispatch_requests'],
            'driver_compliance_document' => [DriverComplianceDocument::findOrFail($data['attachable_id']), 'driver_compliance_documents'],
            'vehicle_compliance_document' => [VehicleComplianceDocument::findOrFail($data['attachable_id']), 'vehicle_compliance_documents'],
        };

        if ($attachable instanceof DriverComplianceDocument) {
            if (! $request->user()?->can('resource.driver.manage')) {
                abort(403);
            }
        }

        if ($attachable instanceof VehicleComplianceDocument) {
            if (! $request->user()?->can('resource.vehicle.manage')) {
                abort(403);
            }
        }

        if ($attachable instanceof TripCost) {
            FinancialDataLock::assertTripCostAllowsNewAttachment($attachable);
        }

        $disk = 'public';
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $path = Storage::putFileAs(
            "attachments/{$folder}/{$attachable->getKey()}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        $attachment = Attachment::create([
            'uploaded_by' => $user?->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => $data['kind'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => $data['attachable_type'],
                'attachable_id' => $data['attachable_id'],
            ],
        );

        return $this->created([
            ...$attachment->toArray(),
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Tải file gốc qua API (Sanctum) — tránh phụ thuộc URL /storage công khai hoặc SPA/nginx trả HTML.
     */
    public function download(Request $request, Attachment $attachment)
    {
        $this->authorizeAttachmentDownload($request, $attachment);

        $disk = $attachment->disk ?: 'public';
        $path = $attachment->path;
        if (! $path) {
            abort(404, 'Bản ghi đính kèm không có đường dẫn tệp (path).');
        }
        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Không tìm thấy tệp trên máy chủ (storage). Kiểm tra `php artisan storage:link`, quyền thư mục, hoặc tải lại chứng từ.');
        }

        $name = $attachment->original_name ?: basename($path);

        return Storage::disk($disk)->download($path, $name);
    }

    /**
     * morphTo đôi khi không resolve (attachable_type cũ / khác class). Thử map thủ công.
     */
    private function resolveAttachableParent(Attachment $attachment): ?Model
    {
        $attachment->loadMissing('attachable');
        if ($attachment->attachable instanceof Model) {
            return $attachment->attachable;
        }

        $type = $attachment->attachable_type;
        $id = $attachment->attachable_id;
        if (! $type || ! $id) {
            return null;
        }

        $type = (string) $type;

        $aliases = [
            'vehicle_compliance_document' => VehicleComplianceDocument::class,
            'driver_compliance_document' => DriverComplianceDocument::class,
            'trip' => Trip::class,
            'dispatch_request' => DispatchRequest::class,
            'cargo_shipment' => CargoShipment::class,
            'trip_cost' => TripCost::class,
        ];

        if (isset($aliases[$type])) {
            return $aliases[$type]::query()->find($id);
        }

        if (class_exists($type)) {
            return $type::query()->find($id);
        }

        return null;
    }

    private function authorizeAttachmentDownload(Request $request, Attachment $attachment): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $parent = $this->resolveAttachableParent($attachment);
        if (! $parent) {
            abort(404, 'Không tìm thấy chứng từ gắn với tệp (orphan hoặc attachable_type/attachable_id không hợp lệ).');
        }

        if ($parent instanceof VehicleComplianceDocument) {
            if ($user->can('resource.vehicle.manage') || $user->can('trip.assign')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof DriverComplianceDocument) {
            if ($user->can('resource.driver.manage')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof Trip) {
            if ($user->can('trip.view_all') || $user->can('trip.assign')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof DispatchRequest) {
            if ($user->can('request.paper.manage') || $user->can('request.approve') || $user->can('request.create')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof CargoShipment) {
            if ($user->can('cargo.manage')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof TripCost) {
            if ($user->can('trip.cost.view') || $user->can('trip.cost.reconcile')) {
                return;
            }
            abort(403);
        }

        abort(403);
    }

    public function runOcr(RunAttachmentOcrRequest $request, Attachment $attachment, PaperOcrStubService $ocr)
    {
        if ($attachment->kind !== 'paper_scan') {
            abort(422, 'Chỉ hỗ trợ OCR cho file loại paper_scan.');
        }

        $ocr->process($attachment->fresh());

        app(AuditLogger::class)->log(
            actorId: $request->user()?->id,
            event: 'attachment.ocr_stub',
            auditable: $attachment,
            before: null,
            after: $attachment->fresh()->toArray(),
        );

        return $this->ok($attachment->fresh());
    }
}
