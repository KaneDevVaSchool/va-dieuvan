<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    public function isConfigured(): bool
    {
        $pub = config('push.vapid.public_key');
        $priv = config('push.vapid.private_key');

        return is_string($pub) && $pub !== '' && is_string($priv) && $priv !== '';
    }

    /**
     * @param  array{title?: string, body?: string, url?: string, tag?: string}  $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $subs = $user->pushSubscriptions()->get();
        if ($subs->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('push.vapid.subject'),
                'publicKey' => config('push.vapid.public_key'),
                'privateKey' => config('push.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $json = json_encode(
            $payload + ['title' => $payload['title'] ?? config('app.name')],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        /** @var PushSubscription $row */
        foreach ($subs as $row) {
            $subscription = Subscription::create([
                'endpoint' => $row->endpoint,
                'keys' => [
                    'p256dh' => $row->public_key,
                    'auth' => $row->auth_token,
                ],
                'contentEncoding' => $row->content_encoding ?: 'aesgcm',
            ]);
            $webPush->sendOneNotification($subscription, $json, [], []);
        }
        $webPush->flush();
    }
}
