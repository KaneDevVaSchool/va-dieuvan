<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\TripVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Tài xế cập nhật trạng thái nhận/giao hàng hoá + tải ảnh minh chứng cho chuyến mình được phân.
 * Chỉ thao tác ở mức cả vận đơn (1 CargoShipment / phiếu hàng hoá).
 */
class DriverCargoController extends Controller
{
    use ApiResponses;

    public function updateStatus(Request $request, CargoShipment $cargoShipment): JsonResponse
    {
        $this->assertDriverOwnsShipment($request->user(), $cargoShipment);

        $data = $request->validate([
            // Tài xế chỉ ghi nhận "đã nhận hàng" (picked_up) và "đã giao hàng" (delivered).
            'status' => ['required', Rule::in(['picked_up', 'delivered'])],
        ]);

        return DB::transaction(function () use ($request, $cargoShipment, $data) {
            $before = $cargoShipment->toArray();

            $updates = ['status' => $data['status']];
            if ($data['status'] === 'picked_up') {
                $updates['picked_up_at'] = $cargoShipment->picked_up_at ?? now();
            }
            if ($data['status'] === 'delivered') {
                // Giao mà chưa từng ghi nhận đã nhận → set luôn picked_up_at để mốc thời gian hợp lệ.
                $updates['picked_up_at'] = $cargoShipment->picked_up_at ?? now();
                $updates['delivered_at'] = $cargoShipment->delivered_at ?? now();
            }

            $cargoShipment->update($updates);

            app(AuditLogger::class)->log(
                actorId: $request->user()?->id,
                event: 'cargo.status_change',
                auditable: $cargoShipment,
                before: $before,
                after: $cargoShipment->toArray(),
                metadata: ['source' => 'DriverCargoController'],
            );

            return $this->ok([
                'shipment' => $cargoShipment->fresh([
                    'attachments' => fn ($q) => $q->where('kind', 'pod')->orderByDesc('id'),
                ]),
            ]);
        });
    }

    public function uploadPod(Request $request, CargoShipment $cargoShipment): JsonResponse
    {
        $this->assertDriverOwnsShipment($request->user(), $cargoShipment);

        $data = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'], // 10MB
        ]);

        return DB::transaction(function () use ($request, $cargoShipment, $data) {
            $user = $request->user();

            /** @var UploadedFile $file */
            $file = $data['file'];

            $disk = 'public';
            $path = Storage::putFileAs(
                "attachments/cargo/{$cargoShipment->id}/pod",
                $file,
                $file->hashName(),
                ['disk' => $disk],
            );

            $attachment = Attachment::create([
                'uploaded_by' => $user?->id,
                'attachable_type' => $cargoShipment->getMorphClass(),
                'attachable_id' => $cargoShipment->getKey(),
                'kind' => 'pod',
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'file_binary' => Attachment::bytesFromUpload($file),
            ]);

            app(AuditLogger::class)->log(
                actorId: $user?->id,
                event: 'cargo.pod.upload',
                auditable: $cargoShipment,
                before: null,
                after: ['attachment_id' => $attachment->id],
                metadata: ['source' => 'DriverCargoController'],
            );

            return $this->created($attachment->fresh());
        });
    }

    private function assertDriverOwnsShipment(?User $user, CargoShipment $cargoShipment): void
    {
        $cargoShipment->loadMissing('trip');
        $trip = $cargoShipment->trip;
        abort_unless(
            $user !== null && $trip !== null && TripVisibility::userCanViewTrip($user, $trip),
            403,
            'Bạn không được thao tác hàng hoá của chuyến này.',
        );
    }
}
