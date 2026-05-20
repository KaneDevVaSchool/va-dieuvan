<?php

namespace App\Notifications\Concerns;

trait AddsMailWhenValidEmail
{
    /**
     * @return array<int, string>
     */
    protected function channelsWithOptionalMail(object $notifiable, bool $includeMail = true): array
    {
        $channels = ['database'];
        if (! $includeMail) {
            return $channels;
        }

        $email = trim((string) ($notifiable->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
