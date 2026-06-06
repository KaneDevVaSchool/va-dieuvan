<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TpStudent\StoreTpStudentRequest;
use App\Http\Requests\Api\TpStudent\UpdateTpStudentRequest;
use App\Models\TpStudent;
use App\Services\TpStudent\TpStudentPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpStudentController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpStudentPresenter $presenter,
    ) {}

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
            ->when($transportStatus, fn ($q) => $this->presenter->applyTransportStatusFilter($q, (string) $transportStatus))
            ->orderBy('full_name')
            ->paginate((int) $request->query('per_page', 15));

        $items = collect($paginator->items())->map(fn (TpStudent $s) => $this->presenter->present($s));

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
        $data = $request->validated();
        $data['metadata'] = $this->cleanMetadata($data['metadata'] ?? []);

        $student = TpStudent::query()->create($data);

        return $this->created($student);
    }

    /**
     * Drop empty metadata entries so we don't persist blank strings.
     */
    private function cleanMetadata(array $metadata): array
    {
        return array_filter($metadata, fn ($value) => $value !== null && $value !== '');
    }

    public function show(TpStudent $tpStudent): JsonResponse
    {
        return $this->ok($tpStudent);
    }

    public function update(UpdateTpStudentRequest $request, TpStudent $tpStudent): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('metadata', $data)) {
            // Merge so keys not present in the form (e.g. legacy values) are preserved.
            $data['metadata'] = $this->cleanMetadata(
                array_merge($tpStudent->metadata ?? [], $data['metadata'] ?? [])
            );
        }

        $tpStudent->update($data);

        return $this->ok($tpStudent->fresh());
    }

    public function destroy(TpStudent $tpStudent): JsonResponse
    {
        $tpStudent->delete();

        return $this->ok(['deleted' => true]);
    }
}
