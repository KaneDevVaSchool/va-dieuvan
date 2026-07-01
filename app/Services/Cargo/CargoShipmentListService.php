<?php

namespace App\Services\Cargo;

use App\Models\CargoShipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CargoShipmentListService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function filteredQuery(array $data): Builder
    {
        $q = CargoShipment::query()
            ->visibleOnStaffCargoIndex()
            ->orderByDesc('id');

        $this->applyListFilters($q, $data);

        return $q;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function applyListFilters(Builder $q, array $data): void
    {
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
    }
}
