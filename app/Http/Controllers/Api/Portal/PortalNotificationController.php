<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\PortalNotificationIndexRequest;
use App\Http\Requests\Api\Portal\PortalNotificationMarkReadRequest;
use App\Models\DispatchRequest;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;

class PortalNotificationController extends Controller
{
    use ApiResponses;

    public function index(PortalNotificationIndexRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $perPage = isset($data['per_page']) ? max(1, min(50, (int) $data['per_page'])) : 20;

        $unreadTotal = $user->unreadNotifications()->count();

        $filter = isset($data['filter']) ? (string) $data['filter'] : 'all';

        $notificationsQuery = $filter === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        $paginator = $notificationsQuery
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $rawItems = collect($paginator->items());

        $dispatchRequestIds = $rawItems
            ->map(fn ($n) => data_get($n->data, 'dispatch_request_id'))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $refByDispatchRequestId = $dispatchRequestIds->isEmpty()
            ? collect()
            : DispatchRequest::query()
                ->whereIn('id', $dispatchRequestIds)
                ->get(['id', 'created_at'])
                ->keyBy('id')
                ->map(fn (DispatchRequest $dr) => DispatchRequestMailPresenter::referenceCode($dr));

        $items = $rawItems->map(function ($n) use ($refByDispatchRequestId) {
            $dispatchRequestId = data_get($n->data, 'dispatch_request_id');
            $ref = null;
            if ($dispatchRequestId !== null && $dispatchRequestId !== '') {
                $ref = $refByDispatchRequestId->get((int) $dispatchRequestId);
            }

            return [
                'id' => $n->id,
                'read' => $n->read_at !== null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'dispatch_request_ref' => $ref,
            ];
        })->values();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'unread_total' => $unreadTotal,
            ],
        ]);
    }

    public function markRead(PortalNotificationMarkReadRequest $request, string $notification): \Illuminate\Http\JsonResponse
    {
        $updated = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok(['updated' => (bool) $updated]);
    }

    public function markAllRead(PortalNotificationMarkReadRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->ok(['ok' => true]);
    }
}
