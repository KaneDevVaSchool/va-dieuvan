<?php

namespace App\Http\Controllers\Api\Cargo;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cargo\BulkDeleteCargoShipmentsRequest;
use App\Http\Requests\Api\Cargo\CreateCargoShipmentRequest;
use App\Http\Requests\Api\Cargo\DestroyCargoShipmentRequest;
use App\Http\Requests\Api\Cargo\ListCargoShipmentsRequest;
use App\Http\Requests\Api\Cargo\PurgeAllCargoShipmentsRequest;
use App\Http\Requests\Api\Cargo\ShowCargoShipmentRequest;
use App\Http\Requests\Api\Cargo\UpdateCargoShipmentStatusRequest;
use App\Http\Requests\Api\Cargo\UploadCargoPodRequest;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Services\Auditing\AuditLogger;
use App\Services\Cargo\CargoShipmentListService;
use App\Services\Cargo\CargoShipmentPurgeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CargoController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly CargoShipmentListService $listService,
        private readonly CargoShipmentPurgeService $purgeService,
    ) {}

    public function index(ListCargoShipmentsRequest $request)
    {
        $data = $request->validated();

        $q = $this->listService->filteredQuery($data)
            ->with([
                'trip:id,status,depart_at,driver_id,vehicle_id',
                'trip.driver:id,full_name,phone',
                'trip.vehicle:id,license_plate',
                'dispatchRequest:id,status,created_at,requester_id',
                'dispatchRequest.requester:id,name,email,employee_code,avatar_url',
                'attachments' => fn ($q) => $q->where('kind', 'pod')->orderByDesc('id'),
            ]);

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

    public function destroy(DestroyCargoShipmentRequest $request, CargoShipment $cargoShipment)
    {
        $deleted = $this->purgeService->purgeByIds($request->user(), [$cargoShipment->id], true);

        abort_if($deleted === 0, 403, 'Không thể xóa đơn hàng này.');

        return $this->ok(['deleted' => $deleted]);
    }

    public function bulkDestroy(BulkDeleteCargoShipmentsRequest $request)
    {
        $ids = array_map('intval', $request->validated('ids'));
        $deleted = $this->purgeService->purgeByIds($request->user(), $ids, true);

        return $this->ok(['deleted' => $deleted]);
    }

    public function purgeAll(PurgeAllCargoShipmentsRequest $request)
    {
        $validated = $request->validated();
        $permanent = (bool) $validated['permanent'];
        $expected = (int) $validated['expected_count'];

        $filterData = collect($validated)
            ->except(['permanent', 'confirm_phrase', 'expected_count', 'per_page', 'page'])
            ->all();

        $total = (int) $this->listService->filteredQuery($filterData)->count();

        if ($total !== $expected) {
            abort(422, 'Số lượng đơn hàng đã thay đổi ('.$total.' ≠ '.$expected.'). Làm mới trang rồi thử lại.');
        }

        if ($total === 0) {
            return $this->ok(['deleted' => 0, 'permanent' => $permanent]);
        }

        $deleted = $this->purgeService->purgeAll($request->user(), $filterData, $permanent);

        return $this->ok([
            'deleted' => $deleted,
            'permanent' => $permanent,
        ]);
    }
}
