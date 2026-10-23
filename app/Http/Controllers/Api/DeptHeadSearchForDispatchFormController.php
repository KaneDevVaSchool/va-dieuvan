<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\SearchDeptHeadsForDispatchFormRequest;
use App\Models\User;
use App\Services\Hr\EmployeeManagerResolver;
use App\Support\DepartmentHeadUserSearch;
use Illuminate\Http\JsonResponse;

class DeptHeadSearchForDispatchFormController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchDeptHeadsForDispatchFormRequest $request): JsonResponse
    {
        if ($request->boolean('suggested')) {
            $requester = $request->user();
            if ($request->filled('requester_id') && ($requester->hasRole('dispatcher') || $requester->hasRole('admin'))) {
                $requester = User::findOrFail($request->integer('requester_id'));
            }
            $manager = app(EmployeeManagerResolver::class)->resolve($requester);

            return $this->ok($manager ? [$manager->only(['id', 'name', 'email', 'employee_code'])] : []);
        }
        $pickId = (int) ($request->validated('pick') ?? 0);
        $q = $request->validated('q');

        return $this->ok(DepartmentHeadUserSearch::search($q, $pickId));
    }
}
