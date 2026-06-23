<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\SearchDeptHeadsForDispatchFormRequest;
use App\Support\DepartmentHeadUserSearch;
use Illuminate\Http\JsonResponse;

class DeptHeadSearchForDispatchFormController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchDeptHeadsForDispatchFormRequest $request): JsonResponse
    {
        $pickId = (int) ($request->validated('pick') ?? 0);
        $q = $request->validated('q');

        return $this->ok(DepartmentHeadUserSearch::search($q, $pickId));
    }
}
