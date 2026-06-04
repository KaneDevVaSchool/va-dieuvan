<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyRoutesRequest;
use App\Http\Requests\Api\P2pPolicy\StorePolicyRouteRequest;
use App\Models\Route;
use App\Models\StudentPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Tuyến P2P (type=policy) — không dùng luồng phiên bản/điểm dừng D2D. */
class PolicyRouteController extends Controller
{
    use ApiResponses;

    public function index(ListPolicyRoutesRequest $request): JsonResponse
    {
        $routes = Route::query()
            ->where('type', Route::TYPE_POLICY)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $policyCounts = StudentPolicy::query()
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->groupBy('route_id')
            ->select('route_id', DB::raw('COUNT(*) as aggregate'))
            ->pluck('aggregate', 'route_id');

        $items = $routes->map(fn (Route $route) => [
            'id' => $route->id,
            'name' => $route->name,
            'type' => $route->type,
            'policy_students_count' => (int) ($policyCounts[$route->id] ?? 0),
        ])->values()->all();

        return $this->ok(['items' => $items]);
    }

    public function store(StorePolicyRouteRequest $request): JsonResponse
    {
        $route = Route::create([
            'name' => $request->validated('name'),
            'type' => Route::TYPE_POLICY,
            'is_active' => true,
        ]);

        return $this->created([
            'id' => $route->id,
            'name' => $route->name,
            'type' => $route->type,
            'policy_students_count' => 0,
        ]);
    }
}
