<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CargoShipment;
use App\Models\Attachment;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CargoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'sender_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $slaHours = (int) ($data['sla_hours'] ?? 3);
        $shipment = CargoShipment::create([
            ...$data,
            'status' => 'pending',
            'sla_due_at' => now()->addHours($slaHours),
        ]);

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'cargo.create',
            auditable: $shipment,
            before: null,
            after: $shipment->toArray(),
        );

        return response()->json(['data' => $shipment], 201);
    }

    public function updateStatus(Request $request, CargoShipment $cargoShipment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['picked_up', 'in_transit', 'delivered', 'failed', 'cancelled'])],
        ]);

        $before = $cargoShipment->toArray();

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'picked_up') {
            $updates['picked_up_at'] = $cargoShipment->picked_up_at ?? now();
        }
        if ($data['status'] === 'delivered') {
            $updates['delivered_at'] = $cargoShipment->delivered_at ?? now();
        }

        $cargoShipment->update($updates);

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'cargo.status_change',
            auditable: $cargoShipment,
            before: $before,
            after: $cargoShipment->toArray(),
        );

        $slaBreached = $cargoShipment->sla_due_at
            ? Carbon::parse($cargoShipment->sla_due_at)->lt(now()) && $cargoShipment->status !== 'delivered'
            : false;

        return response()->json(['data' => ['shipment' => $cargoShipment, 'sla_breached' => $slaBreached]]);
    }

    public function uploadPod(Request $request, CargoShipment $cargoShipment)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

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
        ]);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'cargo.pod.upload',
            auditable: $cargoShipment,
            before: null,
            after: ['attachment_id' => $attachment->id],
        );

        return response()->json([
            'data' => [
                ...$attachment->toArray(),
                'url' => Storage::url($path),
            ],
        ], 201);
    }
}

