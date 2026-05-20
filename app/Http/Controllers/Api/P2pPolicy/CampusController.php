<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\ListCampusesRequest;
use App\Http\Requests\Api\P2pPolicy\StoreCampusRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateCampusRequest;
use App\Models\Campus;
use App\Services\Auditing\AuditLogger;

class CampusController extends Controller
{
    use ApiResponses;

    public function index(ListCampusesRequest $request)
    {
        $data = $request->validated();
        $q = Campus::query()->orderBy('name');

        if (! empty($data['q'])) {
            $like = '%'.addcslashes($data['q'], '%_\\').'%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)->orWhere('code', 'like', $like);
            });
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

    public function store(StoreCampusRequest $request)
    {
        $data = $request->validated();
        $campus = Campus::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        app(AuditLogger::class)->log($request->user()->id, 'campus.create', $campus, null, $campus->toArray());

        return $this->created($campus);
    }

    public function update(UpdateCampusRequest $request, Campus $campus)
    {
        $before = $campus->toArray();
        $campus->update($request->validated());

        app(AuditLogger::class)->log($request->user()->id, 'campus.update', $campus, $before, $campus->toArray());

        return $this->ok($campus);
    }
}
