<?php

namespace App\Services\Expenses;

use App\Models\ExpenseDocument;
use App\Models\ExpenseDocumentLine;
use App\Models\TripCost;
use App\Models\User;
use App\Support\TripCostAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpenseQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?User $onlyRequester = null): LengthAwarePaginator
    {
        $q = ExpenseDocument::query()
            ->with(['vehicle:id,license_plate', 'parent:id,code'])
            ->withCount('lines');

        if ($onlyRequester !== null) {
            $q->where('requester_user_id', $onlyRequester->id);
        }

        $q->when($filters['doc_type'] ?? null, fn (Builder $b, $v) => $b->where('doc_type', $v))
            ->when($filters['payment_kind'] ?? null, fn (Builder $b, $v) => $b->where('payment_kind', $v))
            ->when($filters['status'] ?? null, fn (Builder $b, $v) => $b->whereIn('status', (array) $v))
            ->when($filters['driver_id'] ?? null, fn (Builder $b, $v) => $b->where('driver_id', $v))
            ->when($filters['vehicle_id'] ?? null, fn (Builder $b, $v) => $b->where(fn (Builder $w) => $w->where('vehicle_id', $v)
                ->orWhereHas('lines', fn (Builder $l) => $l->where('vehicle_id', $v))))
            ->when($filters['date_from'] ?? null, fn (Builder $b, $v) => $b->whereDate('proposed_on', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $b, $v) => $b->whereDate('proposed_on', '<=', $v))
            ->when($filters['q'] ?? null, function (Builder $b, $v) {
                $like = '%'.$v.'%';
                $b->where(fn (Builder $w) => $w->where('code', 'like', $like)
                    ->orWhere('purpose', 'like', $like)
                    ->orWhere('requester_name', 'like', $like));
            });

        return $q->orderByDesc('id')->paginate(min(100, max(1, (int) ($filters['per_page'] ?? 20))));
    }

    /**
     * Chi phí đã khai có thể đưa vào hồ sơ: người dùng thấy được, chưa bị từ chối,
     * chưa nằm trong hồ sơ cùng loại khác (trừ hồ sơ đang sửa).
     *
     * @return list<array<string, mixed>>
     */
    public function costCandidates(User $user, string $docType, ?int $vehicleId, ?int $excludeDocumentId = null): array
    {
        $q = TripCost::query()
            ->with(['vehicle:id,license_plate', 'trip:id,vehicle_id,depart_at', 'trip.vehicle:id,license_plate'])
            ->whereIn('status', ['draft', 'submitted', 'confirmed'])
            ->whereDoesntHave('expenseLines', fn (Builder $l) => $l
                ->when($excludeDocumentId, fn (Builder $x) => $x->where('document_id', '!=', $excludeDocumentId))
                ->whereHas('document', fn (Builder $d) => $d->where('doc_type', $docType)->whereIn('status', ExpenseDocument::HOLDING_STATUSES)));

        TripCostAccess::applyVisibleToUserScope($q, $user);

        if (! $user->hasPermission('trip.view_all')) {
            $q->where('created_by', $user->id);
        }

        if ($vehicleId) {
            $q->where(fn (Builder $w) => $w->where('vehicle_id', $vehicleId)
                ->orWhere(fn (Builder $x) => $x->whereNull('vehicle_id')->whereHas('trip', fn (Builder $t) => $t->where('vehicle_id', $vehicleId))));
        }

        return $q->orderByDesc('id')->limit(200)->get()->map(fn (TripCost $c) => [
            'id' => $c->id,
            'type' => $c->type,
            'amount' => (float) $c->amount,
            'description' => $c->description,
            'reported_on' => $c->reported_on?->toDateString() ?? $c->created_at?->toDateString(),
            'status' => $c->status,
            'trip_id' => $c->trip_id,
            'vehicle_id' => $c->vehicle_id ?? $c->trip?->vehicle_id,
            'license_plate' => $c->vehicle?->license_plate ?? $c->trip?->vehicle?->license_plate,
            'has_invoice' => (bool) ($c->has_invoice ?? true),
            'seller_name' => $c->seller_name,
            'seller_address' => $c->seller_address,
            'unit' => $c->unit,
            'quantity' => $c->quantity !== null ? (float) $c->quantity : null,
            'unit_price' => $c->unit_price !== null ? (float) $c->unit_price : null,
        ])->values()->all();
    }

    /**
     * Hồ sơ có thể làm căn cứ: DX (cho TT/HU), TU (cho HU), TT/HU (cho BK) của chính người dùng, chưa hủy.
     *
     * @return list<array<string, mixed>>
     */
    public function parentCandidates(User $user): array
    {
        return ExpenseDocument::query()
            ->where('requester_user_id', $user->id)
            ->whereNotIn('status', [ExpenseDocument::STATUS_CANCELLED])
            ->where(fn (Builder $b) => $b->where('doc_type', ExpenseDocument::TYPE_PROPOSAL)
                ->orWhere('doc_type', ExpenseDocument::TYPE_PAYMENT_REQUEST))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'code', 'doc_type', 'payment_kind', 'status', 'purpose', 'total_amount', 'vehicle_id'])
            ->map(fn (ExpenseDocument $d) => [
                'id' => $d->id,
                'code' => $d->code,
                'doc_type' => $d->doc_type,
                'payment_kind' => $d->payment_kind,
                'type_label' => ExpensePresenter::typeLabel($d),
                'status' => $d->status,
                'purpose' => $d->purpose,
                'total_amount' => (float) $d->total_amount,
                'vehicle_id' => $d->vehicle_id,
            ])->values()->all();
    }

    /**
     * Hồ sơ đang "giữ" một chi phí (hiển thị ở sổ chi phí xe).
     *
     * @param  list<int>  $costIds
     * @return array<int, array{code: string, status: string, id: int}>
     */
    public function holdersForCosts(array $costIds): array
    {
        if ($costIds === []) {
            return [];
        }

        return ExpenseDocumentLine::query()
            ->whereIn('trip_cost_id', $costIds)
            ->whereHas('document', fn (Builder $d) => $d->where('doc_type', ExpenseDocument::TYPE_PAYMENT_REQUEST)
                ->whereIn('status', ExpenseDocument::HOLDING_STATUSES))
            ->with('document:id,code,status')
            ->get()
            ->mapWithKeys(fn (ExpenseDocumentLine $l) => [
                (int) $l->trip_cost_id => ['id' => $l->document->id, 'code' => $l->document->code, 'status' => $l->document->status],
            ])
            ->all();
    }
}
