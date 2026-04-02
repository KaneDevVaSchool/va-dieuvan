<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttachmentController extends Controller
{
    public function upload(Request $request)
    {
        $data = $request->validate([
            'attachable_type' => ['required', Rule::in(['trip', 'cargo_shipment', 'trip_cost', 'dispatch_request'])],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', 'max:50'], // pod, receipt...
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $user = $request->user();

        [$attachable, $folder] = match ($data['attachable_type']) {
            'trip' => [Trip::findOrFail($data['attachable_id']), 'trips'],
            'cargo_shipment' => [CargoShipment::findOrFail($data['attachable_id']), 'cargo'],
            'trip_cost' => [TripCost::findOrFail($data['attachable_id']), 'costs'],
            'dispatch_request' => [DispatchRequest::findOrFail($data['attachable_id']), 'dispatch_requests'],
        };

        $disk = 'public';
        /** @var UploadedFile $file */
        $file = $request->file('file');

        // Use facade helper to satisfy static analysis
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

        return response()->json([
            'data' => [
                ...$attachment->toArray(),
                'url' => Storage::url($path),
            ],
        ], 201);
    }
}

