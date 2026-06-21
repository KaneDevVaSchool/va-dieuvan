<?php

namespace App\Services\Costs;

use App\Models\Trip;
use App\Models\User;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BusinessPersonnelCostLinesQuery
{
    /**
     * @param  array{trip_id?: int, from?: string, to?: string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function linesFor(User $user, array $filters): array
    {
        $q = Trip::query()
            ->with([
                'dispatchRequest:id,trip_type,requester_id,wizard_snapshot,origin,destination',
                'dispatchRequest.requester:id,name,email,avatar_url',
            ])
            ->whereHas(
                'dispatchRequest',
                fn (Builder $dr) => $dr->where('trip_type', 'business'),
            )
            ->orderByDesc('id');

        if (! $user->hasPermission('trip.view_all')) {
            $tripIds = TripVisibility::visibleTripsQuery($user)->pluck('id');
            $q->whereIn('id', $tripIds);
        }

        if (isset($filters['trip_id'])) {
            $q->where('id', (int) $filters['trip_id']);
        }

        if (! empty($filters['from'])) {
            $q->where('depart_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $q->where('depart_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $trips = $q->limit(150)->get();

        $out = [];
        foreach ($trips as $trip) {
            $dr = $trip->dispatchRequest;
            if (! $dr) {
                continue;
            }
            $dr->makeVisible(['wizard_snapshot']);
            $snap = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : [];
            $rows = $snap['businessRows'] ?? [];
            if (! is_array($rows)) {
                continue;
            }
            $requesterName = $dr->requester?->name;
            $requesterAvatarUrl = $dr->requester?->avatar_url;
            $requesterEmail = $dr->requester?->email;
            $lineNo = 0;
            foreach ($rows as $row) {
                if (! is_array($row) || ! $this->isBusinessRowFilled($row)) {
                    continue;
                }
                $lineNo++;
                $unit = $this->parseMoney($row['unit_price'] ?? null);
                $extra = $this->parseMoney($row['extra_fee'] ?? null);
                $personnel = trim((string) ($row['description'] ?? ''));
                if ($personnel === '') {
                    $personnel = trim((string) ($row['notes'] ?? ''));
                }
                if ($personnel === '') {
                    $personnel = trim((string) ($row['waypoint'] ?? ''));
                }
                $out[] = [
                    'trip_id' => $trip->id,
                    'line_no' => $lineNo,
                    'requester_name' => $requesterName,
                    'requester_avatar_url' => $requesterAvatarUrl,
                    'requester_email' => $requesterEmail,
                    'personnel_label' => $personnel !== '' ? $personnel : null,
                    'guests' => trim((string) ($row['guests'] ?? '')),
                    'unit_price' => $unit,
                    'extra_fee' => $extra,
                    'amount_total' => $unit + $extra,
                    'depart_at' => $this->nullableString($row['depart_at'] ?? null),
                    'pickup' => $this->nullableString($row['pickup'] ?? null),
                    'dropoff' => $this->nullableString($row['dropoff'] ?? null),
                    'waypoint' => $this->nullableString($row['waypoint'] ?? null),
                    'notes' => $this->nullableString($row['notes'] ?? null),
                    'trip_depart_at' => $trip->depart_at?->toIso8601String(),
                    'request_origin' => $dr->origin,
                    'request_destination' => $dr->destination,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBusinessRowFilled(array $row): bool
    {
        if (trim((string) ($row['pickup'] ?? '')) !== ''
            || trim((string) ($row['dropoff'] ?? '')) !== ''
            || trim((string) ($row['waypoint'] ?? '')) !== ''
            || trim((string) ($row['depart_at'] ?? '')) !== ''
            || trim((string) ($row['return_at'] ?? '')) !== '') {
            return true;
        }
        if ($this->parseMoney($row['unit_price'] ?? null) > 0) {
            return true;
        }
        if ($this->parseMoney($row['extra_fee'] ?? null) > 0) {
            return true;
        }
        if (trim((string) ($row['notes'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($row['description'] ?? '')) !== '') {
            return true;
        }
        $g = trim((string) ($row['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    private function parseMoney(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return max(0.0, (float) $v);
        }
        $s = preg_replace('/[^\d.-]/', '', (string) $v) ?? '';
        if ($s === '' || ! is_numeric($s)) {
            return 0.0;
        }

        return max(0.0, (float) $s);
    }

    private function nullableString(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));

        return $s !== '' ? $s : null;
    }
}
