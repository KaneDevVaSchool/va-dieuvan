<?php

return [

    /**
     * Web Push (VAPID). Tạo cặp khóa: npx web-push generate-vapid-keys
     * hoặc: openssl ecparam -name prime256v1 -genkey -noout
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:ops@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
