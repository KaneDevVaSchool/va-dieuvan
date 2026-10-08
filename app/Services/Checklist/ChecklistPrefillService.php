<?php

namespace App\Services\Checklist;

use App\Models\ChecklistTemplateItem;
use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Models\TpProgramDay;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use App\Services\Resources\ComplianceDocumentService;
use App\Support\TripScheduleInstant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Điền sẵn phiếu checklist:
 * - Nhóm "Thông tin chuyến" (ngày, cơ sở/tuyến, biển số, giờ xuất bến, tài xế) từ chuyến / ngày TP.
 * - Gợi ý cho các mục giấy tờ (auto_source) theo hạn trong kho chứng từ xe / tài xế.
 *   Gợi ý chỉ để tham khảo — tài xế vẫn phải tự bấm OK/NG.
 */
class ChecklistPrefillService
{
    /** Cột hạn trên bảng chủ dùng khi chưa có chứng từ scan. */
    private const VEHICLE_FALLBACK_COLUMNS = [
        'inspection_certificate' => 'inspection_expires_at',
        'insurance_certificate' => 'insurance_expires_at',
    ];

    private const DRIVER_FALLBACK_COLUMNS = [
        'license' => 'license_expires_at',
    ];

    public function __construct(private readonly ComplianceDocumentService $compliance) {}

    /**
     * @return array<string, string|null>
     */
    public function tripInfo(Driver $driver, ?Vehicle $vehicle, ?Trip $trip = null, ?TpProgramDay $tpDay = null): array
    {
        $now = Carbon::now(TripScheduleInstant::DISPLAY_TZ);
        $route = null;
        $departure = null;

        if ($trip !== null) {
            $trip->loadMissing('dispatchRequest:id,origin,destination');
            $dr = $trip->dispatchRequest;
            if ($dr !== null) {
                $route = trim(implode(' → ', array_filter([(string) $dr->origin, (string) $dr->destination])));
            }
            if ($trip->depart_at !== null) {
                $departure = $trip->depart_at->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ)->format('H:i');
            }
        } elseif ($tpDay !== null) {
            $tpDay->loadMissing('program:id,code,name,origin_name,destination_name,departure_time');
            $p = $tpDay->program;
            if ($p !== null) {
                $route = trim(implode(' · ', array_filter([
                    (string) $p->name,
                    trim(implode(' → ', array_filter([(string) $p->origin_name, (string) $p->destination_name]))),
                ])));
                $departure = $p->departure_time ? substr((string) $p->departure_time, 0, 5) : null;
            }
        }

        return [
            'date' => $now->format('d/m/Y'),
            'campus_route' => $route ?: null,
            'license_plate' => $vehicle?->license_plate,
            'departure_time' => $departure,
            'driver_name' => $driver->full_name,
            'driver_phone' => $driver->phone,
            'attendant' => null,
        ];
    }

    /**
     * Gợi ý theo auto_source, key = template_item_id.
     *
     * @param  Collection<int, ChecklistTemplateItem>  $items
     * @return array<int, array<string, mixed>>
     */
    public function hints(Collection $items, Driver $driver, ?Vehicle $vehicle): array
    {
        $sources = $items->pluck('auto_source')->filter()->unique();
        if ($sources->isEmpty()) {
            return [];
        }

        $driverDocs = DriverComplianceDocument::query()
            ->where('driver_id', $driver->id)
            ->active()
            ->get(['id', 'doc_type', 'expires_at'])
            ->groupBy('doc_type');

        $vehicleDocs = $vehicle
            ? VehicleComplianceDocument::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('status', VehicleComplianceDocument::STATUS_ACTIVE)
                ->get(['id', 'doc_type', 'expires_at'])
                ->groupBy('doc_type')
            : collect();

        $out = [];
        foreach ($items as $item) {
            if (! $item->auto_source || ! str_contains($item->auto_source, ':')) {
                continue;
            }
            [$owner, $docType] = explode(':', $item->auto_source, 2);

            if ($owner === 'driver_doc') {
                $out[$item->id] = $this->hintFor('driver_doc', $docType, $driverDocs->get($docType), $driver->{self::DRIVER_FALLBACK_COLUMNS[$docType] ?? ''} ?? null);
            } elseif ($owner === 'vehicle_doc' && $vehicle !== null) {
                $out[$item->id] = $this->hintFor('vehicle_doc', $docType, $vehicleDocs->get($docType), $vehicle->{self::VEHICLE_FALLBACK_COLUMNS[$docType] ?? ''} ?? null);
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, DriverComplianceDocument|VehicleComplianceDocument>|null  $docs
     * @return array<string, mixed>
     */
    private function hintFor(string $source, string $docType, ?Collection $docs, mixed $fallbackExpiry): array
    {
        $hasDoc = $docs !== null && $docs->isNotEmpty();

        /** @var Carbon|null $expires */
        $expires = $hasDoc ? $docs->pluck('expires_at')->filter()->sortDesc()->first() : null;
        if ($expires === null && $fallbackExpiry !== null) {
            $expires = Carbon::parse($fallbackExpiry);
        }

        if (! $hasDoc && $expires === null) {
            return ['source' => $source, 'doc_type' => $docType, 'state' => 'missing', 'expires_at' => null, 'suggested' => null];
        }

        $state = $this->compliance->expiryState($expires ? Carbon::parse($expires) : null);

        return [
            'source' => $source,
            'doc_type' => $docType,
            'state' => $state['state'],
            'expires_at' => $state['until'],
            'suggested' => match ($state['state']) {
                'exp' => 'ng',
                'ok', 'soon', 'none' => 'ok',
                default => null,
            },
        ];
    }
}
