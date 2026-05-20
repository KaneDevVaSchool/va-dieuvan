<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\AssignPolicyRouteRequest;
use App\Http\Requests\Api\P2pPolicy\ListPolicyRoutesRequest;
use App\Http\Requests\Api\P2pPolicy\StorePolicyRouteRequest;
use App\Http\Requests\Api\P2pPolicy\UpdatePolicyRouteRequest;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Services\Auditing\AuditLogger;

class PolicyRouteController extends Controller
{
    use ApiResponses;

    public function index(ListPolicyRoutesRequest $request)
    {
        $data = $request->validated();
        $q = PolicyRoute::query()
            ->with(['originCampus', 'destCampus', 'vehicle', 'driver', 'backupDriver', 'p2pPolicyTerm.academicTerm'])
            ->withCount('policyStudents')
            ->orderBy('name');

        if (! empty($data['p2p_policy_term_id'])) {
            $q->where('p2p_policy_term_id', (int) $data['p2p_policy_term_id']);
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

    public function show(ListPolicyRoutesRequest $request, PolicyRoute $policyRoute)
    {
        $policyRoute->load([
            'originCampus',
            'destCampus',
            'vehicle',
            'driver',
            'backupDriver',
            'p2pPolicyTerm.academicTerm',
            'policyStudents' => fn ($q) => $q->orderBy('student_name'),
        ]);

        return $this->ok($policyRoute);
    }

    public function store(StorePolicyRouteRequest $request)
    {
        $data = $request->validated();
        $term = P2pPolicyTerm::query()->findOrFail($data['p2p_policy_term_id']);
        if ($term->status !== 'draft') {
            abort(409, 'Chỉ thêm tuyến khi kỳ P2P Policy đang ở trạng thái nháp.');
        }

        $route = PolicyRoute::create([
            'p2p_policy_term_id' => $data['p2p_policy_term_id'],
            'name' => $data['name'],
            'origin_campus_id' => $data['origin_campus_id'],
            'dest_campus_id' => $data['dest_campus_id'],
            'morning_start' => $this->nullableTime($data['morning_start'] ?? null),
            'morning_end' => $this->nullableTime($data['morning_end'] ?? null),
            'afternoon_start' => $this->nullableTime($data['afternoon_start'] ?? null),
            'afternoon_end' => $this->nullableTime($data['afternoon_end'] ?? null),
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.route.create', $route, null, $route->toArray());

        return $this->created($route->load(['originCampus', 'destCampus']));
    }

    public function update(UpdatePolicyRouteRequest $request, PolicyRoute $policyRoute)
    {
        $term = $policyRoute->p2pPolicyTerm;
        if ($term && $term->status !== 'draft') {
            abort(409, 'Chỉ chỉnh sửa tuyến khi kỳ đang ở trạng thái nháp.');
        }

        $data = $request->validated();
        foreach (['morning_start', 'morning_end', 'afternoon_start', 'afternoon_end'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->nullableTime($data[$key]);
            }
        }

        $before = $policyRoute->toArray();
        $policyRoute->update($data);

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.route.update', $policyRoute, $before, $policyRoute->toArray());

        return $this->ok($policyRoute->fresh()->load(['originCampus', 'destCampus']));
    }

    public function assign(AssignPolicyRouteRequest $request, PolicyRoute $policyRoute)
    {
        $before = $policyRoute->toArray();
        $policyRoute->update($request->validated());

        app(AuditLogger::class)->log($request->user()->id, 'p2p_policy.route.assign', $policyRoute, $before, $policyRoute->toArray());

        return $this->ok($policyRoute->fresh()->load(['vehicle', 'driver', 'backupDriver']));
    }

    private function nullableTime(?string $hm): ?string
    {
        if ($hm === null || $hm === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $hm) ? "{$hm}:00" : $hm;
    }
}
