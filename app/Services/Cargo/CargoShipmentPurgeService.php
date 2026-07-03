<?php

namespace App\Services\Cargo;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CargoShipmentPurgeService
{
    public function __construct(
        private readonly CargoShipmentListService $listService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filterData
     */
    public function purgeAll(User $user, array $filterData, bool $permanent): int
    {
        $q = $this->listService->filteredQuery($filterData);
        $deleted = 0;

        DB::transaction(function () use ($q, $user, $permanent, &$deleted) {
            foreach ($q->lazyById(100, 'id', 'id') as $shipment) {
                /** @var CargoShipment $shipment */
                if ($this->purgeOne($user, $shipment, $permanent)) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    /**
     * @param  list<int>  $ids
     */
    public function purgeByIds(User $user, array $ids, bool $permanent): int
    {
        $deleted = 0;

        DB::transaction(function () use ($user, $ids, $permanent, &$deleted) {
            $shipments = CargoShipment::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get();

            foreach ($shipments as $shipment) {
                if ($this->purgeOne($user, $shipment, $permanent)) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    private function purgeOne(User $user, CargoShipment $shipment, bool $permanent): bool
    {
        $before = $shipment->toArray();

        if ($permanent) {
            $this->auditLogger->log(
                actorId: $user->id,
                event: 'cargo.purge.permanent',
                auditable: $shipment,
                before: $before,
                after: null,
            );
            $this->destroyShipmentWithAttachments($shipment);
            $this->forceDeleteLinkedDispatchRequest($user, (int) $before['dispatch_request_id']);

            return true;
        }

        if ($shipment->dispatch_request_id) {
            $dr = DispatchRequest::query()->find($shipment->dispatch_request_id);
            if (! $dr || $dr->trashed()) {
                return false;
            }
            if (! $user->can('delete', $dr)) {
                return false;
            }
            $dr->delete();
            $this->auditLogger->log(
                actorId: $user->id,
                event: 'cargo.purge.soft',
                auditable: $shipment,
                before: $before,
                after: ['dispatch_request_trashed' => $dr->id],
            );

            return true;
        }

        $this->auditLogger->log(
            actorId: $user->id,
            event: 'cargo.purge.soft',
            auditable: $shipment,
            before: $before,
            after: null,
        );
        $this->destroyShipmentWithAttachments($shipment);

        return true;
    }

    private function destroyShipmentWithAttachments(CargoShipment $shipment): void
    {
        $shipment->loadMissing('attachments');
        foreach ($shipment->attachments as $attachment) {
            if ($attachment->disk && $attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
            $attachment->delete();
        }
        $shipment->delete();
    }

    private function forceDeleteLinkedDispatchRequest(User $user, ?int $dispatchRequestId): void
    {
        if (! $dispatchRequestId) {
            return;
        }

        $dr = DispatchRequest::withTrashed()->find($dispatchRequestId);
        if (! $dr) {
            return;
        }

        if ($dr->trashed()) {
            if (! $user->can('forceDelete', $dr)) {
                return;
            }
        } elseif (! $user->can('delete', $dr)) {
            return;
        }

        $dr->forceDelete();
    }
}
