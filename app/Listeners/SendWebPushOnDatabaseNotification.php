<?php

namespace App\Listeners;

use App\Services\WebPushSender;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Str;

class SendWebPushOnDatabaseNotification
{
    public function __construct(
        private WebPushSender $webPush
    ) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $this->webPush->isConfigured()) {
            return;
        }

        $n = $event->notification;
        if (! is_object($n)) {
            return;
        }

        /** @var array<string, mixed> $data */
        if (method_exists($n, 'toArray')) {
            $data = $n->toArray($event->notifiable);
        } elseif (method_exists($n, 'toDatabase')) {
            $data = $n->toDatabase($event->notifiable);
        } else {
            return;
        }
        $title = $data['title'] ?? (string) config('app.name');
        $body = (string) ($data['message'] ?? $data['body'] ?? '');
        if ($body === '' && is_array($data) && $data !== []) {
            $body = Str::limit(json_encode($data, JSON_UNESCAPED_UNICODE), 180, '…');
        }

        if (! is_object($event->notifiable) || ! method_exists($event->notifiable, 'getKey')) {
            return;
        }

        $this->webPush->sendToUser($event->notifiable, [
            'title' => $title,
            'body' => $body,
            'url' => $data['url'] ?? $data['action_url'] ?? '/',
            'tag' => 'va-'.($data['id'] ?? uniqid('n', true)),
        ]);
    }
}
