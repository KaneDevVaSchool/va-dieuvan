<?php

namespace App\Http\Controllers\Api\Cargo;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cargo\CreateCargoShipmentRequest;
use App\Http\Requests\Api\Cargo\ListCargoShipmentsRequest;
use App\Http\Requests\Api\Cargo\ShowCargoShipmentRequest;
use App\Http\Requests\Api\Cargo\UpdateCargoShipmentStatusRequest;
use App\Http\Requests\Api\Cargo\UploadCargoPodRequest;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Services\Auditing\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CargoController extends Controller
{
    use ApiResponses;

    public function index(ListCargoShipmentsRequest $request)
    {
        $data = $request->validated();

        $q = CargoShipment::query()
            ->with([
                'trip:id,status,depart_at',
                'dispatchRequest:id,status',
                'attachments' => fn ($q) => $q->where('kind', 'pod')->orderByDesc('id'),
            ])
            ->orderByDesc('id');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));

        $perPage = (int) ($data['per_page'] ?? 20);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function show(ShowCargoShipmentRequest $request, CargoShipment $cargoShipment)
    {
        $cargoShipment->load(['trip', 'dispatchRequest', 'attachments']);

        return $this->ok($cargoShipment);
    }

    public function store(CreateCargoShipmentRequest $request)
    {
        $data = $request->validated();

        $slaHours = (int) ($data['sla_hours'] ?? 3);
        $shipment = CargoShipment::create([
            ...$data,
            'status' => 'pending',
            'sla_due_at' => now()->addHours($slaHours),
        ]);

        if ($shipment->tracking_code === null || $shipment->tracking_code === '') {
            $shipment->update([
                'tracking_code' => 'CGO-'.str_pad((string) $shipment->id, 8, '0', STR_PAD_LEFT),
            ]);
        }

        $shipment->refresh();

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'cargo.create',
            auditable: $shipment,
            before: null,
            after: $shipment->toArray(),
        );

        return $this->created($shipment);
    }

    public function updateStatus(UpdateCargoShipmentStatusRequest $request, CargoShipment $cargoShipment)
    {
        $data = $request->validated();

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

        return $this->ok(['shipment' => $cargoShipment, 'sla_breached' => $slaBreached]);
    }

    public function uploadPod(UploadCargoPodRequest $request, CargoShipment $cargoShipment)
    {
        $data = $request->validated();

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

        return $this->created([
            ...$attachment->toArray(),
            'url' => Storage::url($path),
        ]);
    }
}
