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

        $items = TpStudent::query()
            ->search($request->query('search'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('grade'), fn ($q, $g) => $q->where('grade', $g))
            ->when($request->query('class_name'), fn ($q, $c) => $q->where('class_name', $c))
            ->when($request->query('campus_id'), fn ($q, $c) => $q->where('campus_id', $c))
            ->orderBy('full_name')
            ->paginate((int) $request->query('per_page', 25));

        return $this->ok([
            'items' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
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
