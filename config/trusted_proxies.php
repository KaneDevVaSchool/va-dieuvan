<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies — behind LiteSpeed/Nginx TLS termination (CyberPanel, etc.)
    |--------------------------------------------------------------------------
    |
    | Khi Laravel nhận kết nối HTTP “nội bộ” từ proxy, cần tin X-Forwarded-Proto
    | để $request->secure() và cookie phản ánh HTTPS đúng. Đặt TRUSTED_PROXIES=* trên hosting
    | có TLS trước PHP. Không máy chủ trực tiếp ra Internet mà không proxy — giữ null.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '*'),

];
