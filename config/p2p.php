<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transport Program redesign (tp_*) — cấu hình realtime / SSE
    |--------------------------------------------------------------------------
    */
    'sse' => [
        // Khoảng thời gian mỗi lần poll DB trong stream (giây).
        'tick_seconds' => (int) env('TP_SSE_TICK_SECONDS', 3),
        // Thời lượng tối đa giữ một kết nối SSE (giây) trước khi client tự reconnect.
        'max_seconds' => (int) env('TP_SSE_MAX_SECONDS', 60),
    ],

    'import' => [
        // Số dòng tối đa cho 1 batch import học sinh.
        'max_rows' => (int) env('TP_IMPORT_MAX_ROWS', 5000),
    ],
];
