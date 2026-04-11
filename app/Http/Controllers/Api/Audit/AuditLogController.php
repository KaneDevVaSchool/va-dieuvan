<?php

namespace App\Http\Controllers\Api\Audit;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Audit\ListAuditLogsRequest;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    use ApiResponses;

    public function index(ListAuditLogsRequest $request)
    {
        $data = $request->validated();

        $q = AuditLog::query()
            ->with(['actor:id,name,email,employee_code'])
            ->orderByDesc('id');

        $q->when(isset($data['actor_id']), fn (Builder $b) => $b->where('actor_id', $data['actor_id']));
        $q->when(isset($data['event']), fn (Builder $b) => $b->where('event', $data['event']));
        $q->when(isset($data['auditable_type']), fn (Builder $b) => $b->where('auditable_type', $data['auditable_type']));
        $q->when(isset($data['auditable_id']), fn (Builder $b) => $b->where('auditable_id', $data['auditable_id']));

        $q->when(isset($data['from']), function (Builder $b) use ($data) {
            $from = Carbon::parse($data['from'])->startOfDay();
            $b->where('created_at', '>=', $from);
        });
        $q->when(isset($data['to']), function (Builder $b) use ($data) {
            $to = Carbon::parse($data['to'])->endOfDay();
            $b->where('created_at', '<=', $to);
        });

        $perPage = (int) ($data['per_page'] ?? 50);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }
}
