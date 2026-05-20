<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListAcademicTermsRequest;
use App\Http\Requests\Api\P2pPolicy\StoreAcademicTermRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Services\Auditing\AuditLogger;

class AcademicTermController extends Controller
{
    use ApiResponses;

    public function index(ListAcademicTermsRequest $request)
    {
        $data = $request->validated();
        $q = AcademicTerm::query()->orderByDesc('starts_on');

        if (! empty($data['academic_year'])) {
            $q->where('academic_year', $data['academic_year']);
        }
        if (isset($data['is_active'])) {
            $q->where('is_active', (bool) $data['is_active']);
        }

        $perPage = (int) ($data['per_page'] ?? 50);
        $page = $q->paginate($perPage);

        return $this->ok([
            'items' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function store(StoreAcademicTermRequest $request)
    {
        $term = AcademicTerm::create($request->validated());
        app(AuditLogger::class)->log($request->user()->id, 'academic_term.create', $term, null, $term->toArray());

        return $this->created($term);
    }

    public function update(UpdateAcademicTermRequest $request, AcademicTerm $academicTerm)
    {
        $before = $academicTerm->toArray();
        $academicTerm->update($request->validated());
        app(AuditLogger::class)->log($request->user()->id, 'academic_term.update', $academicTerm, $before, $academicTerm->toArray());

        return $this->ok($academicTerm);
    }
}
