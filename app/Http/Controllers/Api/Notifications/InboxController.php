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
        $user = $request->user();
        $unreadTotal = $user->unreadNotifications()->count();

        $audience = $request->query('audience');
        $allowedAudiences = ['driver', 'dispatcher', 'department_head', 'admin'];
        if ($audience !== null && $audience !== '') {
            $audience = (string) $audience;
            if (! in_array($audience, $allowedAudiences, true)) {
                $audience = null;
            }
        } else {
            $audience = null;
        }

        $query = $user->notifications()->orderByDesc('created_at');
        if ($audience !== null) {
            $query->where('data->audience', $audience);
        }

        $paginator = $query->paginate($perPage);

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
                'unread_total' => $unreadTotal,
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
