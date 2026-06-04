<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\SystemDashboardRequest;
use App\Services\System\SystemDashboardService;
use Illuminate\Http\JsonResponse;

class SystemDashboardController extends Controller
{
    use ApiResponses;

    public function __invoke(SystemDashboardRequest $request, SystemDashboardService $dashboard): JsonResponse
    {
        return $this->ok($dashboard->summary());
    }
}
