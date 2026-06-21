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
            ->visibleOnStaffCargoIndex()
            ->with([
                'trip:id,status,depart_at',
                'dispatchRequest:id,status,created_at',
                'attachments' => fn ($q) => $q->where('kind', 'pod')->orderByDesc('id'),
            ])
            ->orderByDesc('id');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));

        $q->when(isset($data['from']), fn (Builder $b) => $b->where(
            'created_at',
            '>=',
            Carbon::parse($data['from'])->startOfDay(),
        ));
        $q->when(isset($data['to']), fn (Builder $b) => $b->where(
            'created_at',
            '<=',
            Carbon::parse($data['to'])->endOfDay(),
        ));

        $q->when(isset($data['q']), function (Builder $b) use ($data) {
            $raw = trim((string) $data['q']);
            if ($raw === '') {
                return;
            }
            $term = '%'.addcslashes($raw, '%_\\').'%';
            $b->where(function (Builder $inner) use ($term) {
                $inner->where('tracking_code', 'like', $term)
                    ->orWhere('pickup_address', 'like', $term)
                    ->orWhere('delivery_address', 'like', $term)
                    ->orWhere('sender_name', 'like', $term)
                    ->orWhere('receiver_name', 'like', $term);
            });
        });

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
        $cargoShipment->load([
            'trip.dispatcher:id,name,email',
            'trip.driver:id,full_name,phone',
            'trip.vehicle:id,license_plate,status',
            'trip.transportProvider:id,name',
            'dispatchRequest:id,status,trip_type,origin,destination,depart_at,arrive_by,notes,requester_id,created_at',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
        ]);

        return $this->ok($cargoShipment);
    }

    /**
     * Dòng thời gian: các mốc vận hành từ shipment (tạo, SLA, lấy hàng, giao hàng).
     */
    public function timeline(ShowCargoShipmentRequest $request, CargoShipment $cargoShipment)
    {
        $items = [];

        $push = function (Carbon $at, string $kind, string $code, ?string $detail = null) use (&$items) {
            $items[] = [
                'at' => $at->toIso8601String(),
                'kind' => $kind,
                'code' => $code,
                'detail' => $detail,
            ];
        };

        if ($cargoShipment->created_at) {
            $push(
                Carbon::parse($cargoShipment->created_at),
                'milestone',
                'created',
                $cargoShipment->tracking_code,
            );
        }
        if ($cargoShipment->sla_due_at) {
            $push(
                Carbon::parse($cargoShipment->sla_due_at),
                'milestone',
                'sla_due',
                null,
            );
        }
        if ($cargoShipment->picked_up_at) {
            $push(
                Carbon::parse($cargoShipment->picked_up_at),
                'milestone',
                'picked_up',
                null,
            );
        }
        if ($cargoShipment->delivered_at) {
            $push(
                Carbon::parse($cargoShipment->delivered_at),
                'milestone',
                'delivered',
                null,
            );
        }

        usort($items, function (array $a, array $b) {
            return strcmp($a['at'], $b['at']);
        });

        return $this->ok(['items' => array_values($items)]);
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
            'file_binary' => Attachment::bytesFromUpload($file),
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
