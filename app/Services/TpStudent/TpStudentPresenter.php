<?php

namespace App\Services\TpStudent;

use App\Models\TpStudent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TpStudentPresenter
{
    public function applyListFilters(Builder $query, Request $request): Builder
    {
        return $this->applyListFiltersFromInput($query, $request->query());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function applyListFiltersFromInput(Builder $query, array $input): Builder
    {
        $transportStatus = $input['transport_status'] ?? null;

        return $query
            ->search($input['search'] ?? null)
            ->when($input['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($input['grade'] ?? null, fn ($q, $g) => $q->where('grade', $g))
            ->when($input['class_name'] ?? null, fn ($q, $c) => $q->where('class_name', $c))
            ->when($input['campus_id'] ?? null, fn ($q, $c) => $q->where('campus_id', $c))
            ->when($input['gender'] ?? null, fn ($q, $g) => $q->where('metadata->gender', $g))
            ->when($input['pickup_point'] ?? null, fn ($q, $p) => $q->where('metadata->pickup_point', $p))
            ->when(! empty($input['address_contains']), function ($q) use ($input) {
                $q->where('address', 'like', '%'.$input['address_contains'].'%');
            })
            ->when(! empty($input['parent_phone']), function ($q) use ($input) {
                $needle = '%'.$input['parent_phone'].'%';
                $q->where(function (Builder $inner) use ($needle) {
                    $inner->where('parent_phone', 'like', $needle)
                        ->orWhere('metadata->father_phone', 'like', $needle)
                        ->orWhere('metadata->mother_phone', 'like', $needle);
                });
            })
            ->when($input['program_id'] ?? null, fn ($q, $p) => $q->whereHas(
                'enrollments',
                fn ($e) => $e->whereNull('unenrolled_at')->where('program_id', $p)
            ))
            ->when($transportStatus, fn ($q) => $this->applyTransportStatusFilter($q, (string) $transportStatus));
    }

    public function applyTransportStatusFilter(Builder $query, string $status): Builder
    {
        $activeEnrollment = fn ($e) => $e->whereNull('unenrolled_at');

        return match ($status) {
            'unregistered' => $query->whereDoesntHave('enrollments', $activeEnrollment),
            'transporting' => $query->whereHas('enrollments', fn ($e) => $activeEnrollment($e)
                ->whereHas('program', fn ($p) => $p->where('status', 'active'))),
            'pending' => $query->whereHas('enrollments', fn ($e) => $activeEnrollment($e)
                ->whereHas('program', fn ($p) => $p->where('status', 'draft'))),
            'paused' => $query->whereHas('enrollments', fn ($e) => $activeEnrollment($e)
                ->whereHas('program', fn ($p) => $p->where('status', 'paused'))),
            default => $query,
        };
    }

    public function present(TpStudent $student): array
    {
        $enrollment = $student->enrollments->first();
        $program = $enrollment?->program;

        $transportStatus = 'unregistered';
        if ($program) {
            $transportStatus = match ($program->status) {
                'active', 'completed' => 'transporting',
                'paused' => 'paused',
                default => 'pending',
            };
        }

        $metadata = $student->metadata ?? [];

        return [
            'id' => $student->id,
            'code' => $student->code,
            'full_name' => $student->full_name,
            'grade' => $student->grade,
            'class_name' => $student->class_name,
            'gender' => $metadata['gender'] ?? null,
            'date_of_birth' => $metadata['date_of_birth'] ?? null,
            'age' => $this->resolveAge($metadata),
            'parent_name' => $student->parent_name,
            'parent_phone' => $student->parent_phone,
            'father_name' => $metadata['father_name'] ?? null,
            'father_phone' => $metadata['father_phone'] ?? null,
            'mother_name' => $metadata['mother_name'] ?? null,
            'mother_phone' => $metadata['mother_phone'] ?? null,
            'address' => $student->address,
            'pickup_point' => $metadata['pickup_point'] ?? null,
            'note' => $metadata['note'] ?? null,
            'status' => $student->status,
            'transport_status' => $transportStatus,
            'program' => $program ? [
                'id' => $program->id,
                'name' => $program->name,
                'code' => $program->code,
                'status' => $program->status,
                'start_date' => optional($program->start_date)->toDateString(),
            ] : null,
        ];
    }

    private function resolveAge(array $metadata): ?int
    {
        if (! empty($metadata['date_of_birth'])) {
            try {
                return \Carbon\Carbon::parse($metadata['date_of_birth'])->age;
            } catch (\Throwable) {
                // fall through to legacy age value
            }
        }

        return isset($metadata['age']) ? (int) $metadata['age'] : null;
    }
}
