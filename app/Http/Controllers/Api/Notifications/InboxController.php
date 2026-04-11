<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $paginator = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($n) {
            return [
                'id' => $n->id,
                'read' => $n->read_at !== null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'type' => class_basename($n->type),
                'data' => $n->data,
            ];
        })->values();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification)
    {
        $updated = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok(['updated' => (bool) $updated]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->ok(['ok' => true]);
    }
}
