<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListPolicyRoutesRequest;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\RouteVersion;
use App\Models\StudentPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PolicyRouteController extends Controller
{
    use ApiResponses;

    public function index(ListPolicyRoutesRequest $request): JsonResponse
    {
        $routes = Route::query()
            ->where('is_active', true)
            ->withCount('versions')
            ->orderBy('name')
            ->get();

        $policyCounts = StudentPolicy::query()
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->groupBy('route_id')
            ->select('route_id', DB::raw('COUNT(*) as aggregate'))
            ->pluck('aggregate', 'route_id');

        $latestVersionIds = RouteVersion::query()
            ->select('route_id', DB::raw('MAX(id) as id'))
            ->groupBy('route_id')
            ->pluck('id', 'route_id');

        $stopCounts = [];
        if ($latestVersionIds->isNotEmpty()) {
            $stopCounts = RouteStop::query()
                ->whereIn('route_version_id', $latestVersionIds->values())
                ->groupBy('route_version_id')
                ->select('route_version_id', DB::raw('COUNT(*) as aggregate'))
                ->pluck('aggregate', 'route_version_id');
        }

        $items = $routes->map(function (Route $route) use ($policyCounts, $latestVersionIds, $stopCounts) {
            $versionId = $latestVersionIds[$route->id] ?? null;

            return [
                'id' => $route->id,
                'name' => $route->name,
                'versions_count' => $route->versions_count,
                'policy_students_count' => (int) ($policyCounts[$route->id] ?? 0),
                'stops_count' => $versionId ? (int) ($stopCounts[$versionId] ?? 0) : 0,
            ];
        })->values()->all();

        return $this->ok(['items' => $items]);
    }
}
