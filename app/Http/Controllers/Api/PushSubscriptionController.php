<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    use ApiResponses;

    public function vapidPublicKey(): JsonResponse
    {
        $k = config('push.vapid.public_key');

        return $this->ok(['publicKey' => $k && $k !== '' ? $k : null]);
    }

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $d = $request->validated();
        $user = $request->user();

        PushSubscription::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $d['endpoint'],
            ],
            [
                'content_encoding' => $d['contentEncoding'] ?? 'aesgcm',
                'public_key' => $d['keys']['p256dh'],
                'auth_token' => $d['keys']['auth'],
            ],
        );

        return $this->ok(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $endpoint = $request->input('endpoint');
        $q = $user->pushSubscriptions();
        if (is_string($endpoint) && $endpoint !== '') {
            $q->where('endpoint', $endpoint);
        }
        $q->delete();

        return $this->ok(['ok' => true]);
    }
}
