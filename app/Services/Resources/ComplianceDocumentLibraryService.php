<?php

namespace App\Services\Resources;

use App\Models\DriverComplianceDocument;
use App\Models\VehicleComplianceDocument;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kho chứng từ: danh sách tập trung chứng từ của toàn bộ xe hoặc tài xế (lọc, tìm, phân trang).
 */
class ComplianceDocumentLibraryService
{
    public function __construct(private readonly ComplianceDocumentService $documents) {}

    /**
     * @param  'vehicle'|'driver'  $ownerType
     * @param  array{doc_type?: string|null, state?: string|null, status?: string|null, q?: string|null, per_page?: int|null}  $filters
     * @return array{items: list<array<string, mixed>>, meta: array<string, int>, summary: array<string, int>}
     */
    public function paginate(string $ownerType, array $filters): array
    {
        $isVehicle = $ownerType === 'vehicle';
        $ownerRelation = $isVehicle ? 'vehicle' : 'driver';
        $ownerColumns = $isVehicle ? 'vehicle:id,license_plate,type' : 'driver:id,full_name,phone';

        $base = $this->baseQuery($ownerType, $filters);

        $summary = $this->summary(clone $base);

        $status = $filters['status'] ?? ComplianceDocumentService::STATUS_ACTIVE;
        if ($status !== 'all') {
            $base->where('status', $status);
        }
        $this->applyExpiryState($base, $filters['state'] ?? null);

        $page = $base
            ->with([
                $ownerColumns,
                'attachments' => fn ($q) => $q->select(ComplianceDocumentService::ATTACHMENT_LIST_COLUMNS)->orderBy('id'),
            ])
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderByDesc('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 25), 1), 100));

        $items = collect($page->items())->map(function (Model $d) use ($ownerRelation, $isVehicle) {
            $owner = $d->{$ownerRelation};

            return [
                ...$this->documents->serialize($d),
                'owner' => $owner ? [
                    'id' => $owner->id,
                    'label' => $isVehicle ? $owner->license_plate : $owner->full_name,
                    'sub' => $isVehicle ? $owner->type : $owner->phone,
                ] : null,
            ];
        })->values()->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'summary' => $summary,
        ];
    }

    /**
     * Lọc chung (loại, tìm kiếm, chủ sở hữu còn tồn tại) — chưa lọc trạng thái để dùng cho thống kê.
     */
    private function baseQuery(string $ownerType, array $filters): Builder
    {
        $isVehicle = $ownerType === 'vehicle';
        $query = $isVehicle ? VehicleComplianceDocument::query() : DriverComplianceDocument::query();
        $ownerRelation = $isVehicle ? 'vehicle' : 'driver';

        // Bỏ chứng từ của xe/tài xế đã xóa mềm.
        $query->whereHas($ownerRelation);

        if (! empty($filters['doc_type'])) {
            $query->where('doc_type', $filters['doc_type']);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $w) use ($like, $ownerRelation, $isVehicle) {
                $w->where('title', 'like', $like)
                    ->orWhere('document_no', 'like', $like)
                    ->orWhereHas($ownerRelation, function (Builder $o) use ($like, $isVehicle) {
                        if ($isVehicle) {
                            $o->where('license_plate', 'like', $like);
                        } else {
                            $o->where('full_name', 'like', $like)->orWhere('phone', 'like', $like);
                        }
                    });
            });
        }

        return $query;
    }

    private function applyExpiryState(Builder $query, ?string $state): void
    {
        $today = Carbon::today()->toDateString();
        $soonEnd = Carbon::today()->addDays(ComplianceDocumentService::EXPIRY_SOON_DAYS)->toDateString();

        match ($state) {
            'exp' => $query->whereNotNull('expires_at')->whereDate('expires_at', '<', $today),
            'soon' => $query->whereDate('expires_at', '>=', $today)->whereDate('expires_at', '<=', $soonEnd),
            'ok' => $query->whereDate('expires_at', '>', $soonEnd),
            'none' => $query->whereNull('expires_at'),
            default => null,
        };
    }

    /**
     * Số lượng theo trạng thái hạn (chỉ bản đang hiệu lực) + số bản đã thay thế.
     *
     * @return array{exp: int, soon: int, ok: int, none: int, active: int, superseded: int}
     */
    private function summary(Builder $query): array
    {
        $today = Carbon::today()->toDateString();
        $soonEnd = Carbon::today()->addDays(ComplianceDocumentService::EXPIRY_SOON_DAYS)->toDateString();
        $active = ComplianceDocumentService::STATUS_ACTIVE;

        $row = $query->toBase()
            ->selectRaw('SUM(CASE WHEN status = ? AND expires_at IS NOT NULL AND DATE(expires_at) < ? THEN 1 ELSE 0 END) AS cnt_exp', [$active, $today])
            ->selectRaw('SUM(CASE WHEN status = ? AND DATE(expires_at) >= ? AND DATE(expires_at) <= ? THEN 1 ELSE 0 END) AS cnt_soon', [$active, $today, $soonEnd])
            ->selectRaw('SUM(CASE WHEN status = ? AND DATE(expires_at) > ? THEN 1 ELSE 0 END) AS cnt_ok', [$active, $soonEnd])
            ->selectRaw('SUM(CASE WHEN status = ? AND expires_at IS NULL THEN 1 ELSE 0 END) AS cnt_none', [$active])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_active', [$active])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cnt_superseded', [ComplianceDocumentService::STATUS_SUPERSEDED])
            ->first();

        return [
            'exp' => (int) ($row->cnt_exp ?? 0),
            'soon' => (int) ($row->cnt_soon ?? 0),
            'ok' => (int) ($row->cnt_ok ?? 0),
            'none' => (int) ($row->cnt_none ?? 0),
            'active' => (int) ($row->cnt_active ?? 0),
            'superseded' => (int) ($row->cnt_superseded ?? 0),
        ];
    }
}
