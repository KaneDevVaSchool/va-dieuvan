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
        $q->when(! empty($data['events']), fn (Builder $b) => $b->whereIn('event', $data['events']));
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
            'summary' => $this->buildSummary($data),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, request: int, file: int, alert: int, system: int}
     */
    private function buildSummary(array $filters): array
    {
        $base = AuditLog::query();

        $base->when(isset($filters['actor_id']), fn (Builder $b) => $b->where('actor_id', $filters['actor_id']));
        $base->when(isset($filters['auditable_type']), fn (Builder $b) => $b->where('auditable_type', $filters['auditable_type']));
        $base->when(isset($filters['from']), function (Builder $b) use ($filters) {
            $from = Carbon::parse($filters['from'])->startOfDay();
            $b->where('created_at', '>=', $from);
        });
        $base->when(isset($filters['to']), function (Builder $b) use ($filters) {
            $to = Carbon::parse($filters['to'])->endOfDay();
            $b->where('created_at', '<=', $to);
        });

        $groups = [
            'request' => ['request.create', 'request.paper_received', 'request.reject', 'request.approve'],
            'file' => ['attachment.upload', 'attachment.ocr_stub'],
            'alert' => ['cargo.sla_breached'],
            'system' => ['api.request'],
        ];

        $out = ['total' => (clone $base)->count()];
        foreach ($groups as $key => $events) {
            $out[$key] = (clone $base)->whereIn('event', $events)->count();
        }

        return $out;
    }
}
