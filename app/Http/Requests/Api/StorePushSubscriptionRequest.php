<?php

namespace App\Http\Requests\Api;

class StorePushSubscriptionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:500'],
            'keys.auth' => ['required', 'string', 'max:500'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ];
    }
}
