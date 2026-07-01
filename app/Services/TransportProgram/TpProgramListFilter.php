<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;
use Illuminate\Database\Eloquent\Builder;

class TpProgramListFilter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(Builder $query, array $data): Builder
    {
        return $query
            ->when($data['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($data['responsible_user_id'] ?? null, fn (Builder $q, $u) => $q->where('responsible_user_id', $u))
            ->when($data['destination_name'] ?? null, fn (Builder $q, $d) => $q->where('destination_name', $d))
            ->when($data['school_year'] ?? null, function (Builder $q, $yearLabel) {
                $year = (int) preg_replace('/\D.*/', '', (string) $yearLabel);
                if ($year > 0) {
                    $q->where(function (Builder $inner) use ($year) {
                        $inner->whereYear('start_date', $year)->orWhereYear('end_date', $year);
                    });
                }
            })
            ->when($data['search'] ?? null, function (Builder $q, $term) {
                $q->where(function (Builder $qq) use ($term) {
                    $qq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
                });
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function filteredQuery(array $data): Builder
    {
        return $this->apply(TpProgram::query(), $data);
    }
}
