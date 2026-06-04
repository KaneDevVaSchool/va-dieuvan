<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TpStudent\StoreTpStudentRequest;
use App\Http\Requests\Api\TpStudent\UpdateTpStudentRequest;
use App\Models\TpStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpStudentController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_student.view') || $user->can('tp_student.manage')), 403);

        $transportStatus = $request->query('transport_status');

        $paginator = TpStudent::query()
            ->with(['enrollments' => function ($q) {
                $q->whereNull('unenrolled_at')
                    ->with('program:id,name,code,status,start_date')
                    ->latest('enrolled_at');
            }])
            ->search($request->query('search'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('grade'), fn ($q, $g) => $q->where('grade', $g))
            ->when($request->query('class_name'), fn ($q, $c) => $q->where('class_name', $c))
            ->when($request->query('campus_id'), fn ($q, $c) => $q->where('campus_id', $c))
            ->when($request->query('program_id'), fn ($q, $p) => $q->whereHas(
                'enrollments',
                fn ($e) => $e->whereNull('unenrolled_at')->where('program_id', $p)
            ))
            ->when($transportStatus, fn ($q) => $this->applyTransportStatusFilter($q, $transportStatus))
            ->orderBy('full_name')
            ->paginate((int) $request->query('per_page', 15));

        $items = collect($paginator->items())->map(fn (TpStudent $s) => $this->presentStudent($s));

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => $this->buildStats(),
            'filter_options' => $this->buildFilterOptions(),
        ]);
    }

    private function applyTransportStatusFilter($query, string $status)
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

    private function presentStudent(TpStudent $student): array
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
            'age' => $metadata['age'] ?? null,
            'parent_name' => $student->parent_name,
            'parent_phone' => $student->parent_phone,
            'address' => $student->address,
            'pickup_point' => $metadata['pickup_point'] ?? null,
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

    private function buildStats(): array
    {
        $total = TpStudent::query()->count();
        $registered = TpStudent::query()
            ->whereHas('enrollments', fn ($e) => $e->whereNull('unenrolled_at'))
            ->count();
        $transporting = TpStudent::query()
            ->whereHas('enrollments', fn ($e) => $e->whereNull('unenrolled_at')
                ->whereHas('program', fn ($p) => $p->where('status', 'active')))
            ->count();
        $pending = TpStudent::query()
            ->whereHas('enrollments', fn ($e) => $e->whereNull('unenrolled_at')
                ->whereHas('program', fn ($p) => $p->where('status', 'draft')))
            ->count();

        return [
            'total' => $total,
            'transporting' => $transporting,
            'pending' => $pending,
            'unregistered' => max($total - $registered, 0),
        ];
    }

    private function buildFilterOptions(): array
    {
        return [
            'grades' => TpStudent::query()
                ->whereNotNull('grade')->where('grade', '!=', '')
                ->distinct()->orderBy('grade')->pluck('grade')->values(),
            'classes' => TpStudent::query()
                ->whereNotNull('class_name')->where('class_name', '!=', '')
                ->distinct()->orderBy('class_name')->pluck('class_name')->values(),
            'programs' => \App\Models\TpProgram::query()
                ->whereIn('status', ['active', 'draft', 'paused'])
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code])
                ->values(),
        ];
    }

    public function store(StoreTpStudentRequest $request): JsonResponse
    {
        $student = TpStudent::query()->create($request->validated());

        return $this->created($student);
    }

    public function show(TpStudent $tpStudent): JsonResponse
    {
        return $this->ok($tpStudent);
    }

    public function update(UpdateTpStudentRequest $request, TpStudent $tpStudent): JsonResponse
    {
        $tpStudent->update($request->validated());

        return $this->ok($tpStudent->fresh());
    }

    public function destroy(TpStudent $tpStudent): JsonResponse
    {
        $tpStudent->delete();

        return $this->ok(['deleted' => true]);
    }
}
