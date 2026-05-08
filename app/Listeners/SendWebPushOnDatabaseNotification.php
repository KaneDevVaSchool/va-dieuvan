<?php

namespace App\Listeners;

use App\Services\WebPushSender;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWebPushOnDatabaseNotification
{
    public function __construct(
        private WebPushSender $webPush
    ) {}

    public function handle(NotificationSent $event): void
    {
        $debug = config('dispatch.debug_notification_log');

        if ($event->channel !== 'database') {
            return;
        }

        if (! $this->webPush->isConfigured()) {
            if ($debug) {
                Log::debug('webpush.skip_not_configured', [
                    'notification' => is_object($event->notification) ? $event->notification::class : null,
                    'channel' => $event->channel,
                ]);
            }

            return;
        }

        $n = $event->notification;
        if (! is_object($n)) {
            if ($debug) {
                Log::debug('webpush.skip_invalid_notification_instance');
            }

            return;
        }

        /** @var array<string, mixed> $data */
        if (method_exists($n, 'toArray')) {
            $data = $n->toArray($event->notifiable);
        } elseif (method_exists($n, 'toDatabase')) {
            $data = $n->toDatabase($event->notifiable);
        } else {
            if ($debug) {
                Log::debug('webpush.skip_no_array_payload', ['class' => $n::class]);
            }

            return;
        }
        $title = Str::limit((string) ($data['title'] ?? config('app.name')), 120, '…');
        $body = (string) ($data['message'] ?? $data['body'] ?? '');
        if ($body === '' && is_array($data) && $data !== []) {
            $body = Str::limit(json_encode($data, JSON_UNESCAPED_UNICODE), 180, '…');
        }
        $body = Str::limit($body, 180, '…');

        if (! is_object($event->notifiable) || ! method_exists($event->notifiable, 'getKey')) {
            if ($debug) {
                Log::debug('webpush.skip_invalid_notifiable');
            }

            return;
        }

        $tripId = $data['trip_id'] ?? null;
        $tag = is_numeric($tripId)
            ? 'va-trip-'.$tripId
            : 'va-'.($data['id'] ?? uniqid('n', true));

        $payload = [
            'title' => $title,
            'body' => $body,
            'url' => $data['url'] ?? $data['action_url'] ?? '/',
            'tag' => $tag,
        ];
        if (array_key_exists('is_urgent', $data)) {
            $payload['is_urgent'] = (bool) $data['is_urgent'];
        }

        if ($debug) {
            Log::info('webpush.delivery_attempt', [
                'user_id' => $event->notifiable->getKey(),
                'notification' => $n::class,
                'tag' => $tag,
                'trip_id' => $data['trip_id'] ?? null,
                'event' => $data['event'] ?? null,
            ]);
        }

        $this->webPush->sendToUser($event->notifiable, $payload);
    }
}
