<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\SearchPortalDeptHeadsRequest;
use App\Services\Hr\EmployeeManagerResolver;
use App\Support\DepartmentHeadUserSearch;
use Illuminate\Http\JsonResponse;

class PortalDeptHeadSearchController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchPortalDeptHeadsRequest $request): JsonResponse
    {
        if ($request->boolean('suggested')) {
            $manager = app(EmployeeManagerResolver::class)->resolve($request->user());

            return $this->ok($manager ? [$manager->only(['id', 'name', 'email', 'employee_code'])] : []);
        }
        $pickId = (int) ($request->validated('pick') ?? 0);
        $q = $request->validated('q');

        return $this->ok(DepartmentHeadUserSearch::search($q, $pickId));
    }
}
