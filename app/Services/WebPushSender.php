<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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

        $debug = config('dispatch.debug_notification_log');
        $subs = $user->pushSubscriptions()->get();
        if ($subs->isEmpty()) {
            if ($debug) {
                Log::debug('webpush.skip_no_subscriptions', ['user_id' => $user->getKey()]);
            }

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

        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    if ($debug) {
                        Log::info('webpush.sent_ok', [
                            'user_id' => $user->getKey(),
                            'endpoint' => method_exists($report, 'getEndpoint') ? $report->getEndpoint() : null,
                        ]);
                    }

                    continue;
                }
                if ($report->isSubscriptionExpired()) {
                    Log::notice('webpush.subscription_expired', [
                        'user_id' => $user->getKey(),
                        'endpoint' => method_exists($report, 'getEndpoint') ? $report->getEndpoint() : null,
                    ]);

                    continue;
                }
                Log::warning('webpush.send_failed', [
                    'user_id' => $user->getKey(),
                    'reason' => $report->getReason(),
                    'endpoint' => method_exists($report, 'getEndpoint') ? $report->getEndpoint() : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('webpush.flush_exception', [
                'user_id' => $user->getKey(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
