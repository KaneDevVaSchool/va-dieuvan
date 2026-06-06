<?php

namespace App\Services\TpStudent;

use App\Models\TpStudent;
use Illuminate\Database\Eloquent\Builder;

class TpStudentPresenter
{
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
