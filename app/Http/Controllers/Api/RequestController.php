<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\ListRequestsRequest;
use App\Models\DispatchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RequestController extends Controller
{
    use ApiResponses;

    public function index(ListRequestsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $q = DispatchRequest::query()
            ->with([
                'requester:id,name,email,employee_code',
                'approver:id,name,email,employee_code',
                'trip',
            ])
            ->orderByDesc('depart_at')
            ->orderByDesc('id');

        if (! $user->hasPermission('trip.view_all')) {
            $q->where('requester_id', $user->id);
        }

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));
        $q->when(isset($data['trip_type']), fn (Builder $b) => $b->where('trip_type', $data['trip_type']));
        $q->when(isset($data['source_channel']), fn (Builder $b) => $b->where('source_channel', $data['source_channel']));
        $q->when(isset($data['paper_status']), fn (Builder $b) => $b->where('paper_status', $data['paper_status']));

        $q->when(isset($data['from']), function (Builder $b) use ($data) {
            $from = Carbon::parse($data['from'])->startOfDay();
            $b->where('depart_at', '>=', $from);
        });
        $q->when(isset($data['to']), function (Builder $b) use ($data) {
            $to = Carbon::parse($data['to'])->endOfDay();
            $b->where('depart_at', '<=', $to);
        });

        $perPage = (int) ($data['per_page'] ?? 20);
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
