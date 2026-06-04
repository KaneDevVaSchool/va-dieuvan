<?php

$defaultDebugNotify = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

return [
    /**
     * Log chi tiết luồng thông báo gán chuyến / Web Push (DispatchingService + listener).
     * Mặc định theo APP_DEBUG; có thể ép bằng DISPATCH_DEBUG_NOTIFICATION_LOG=true|false.
     */
    'debug_notification_log' => env('DISPATCH_DEBUG_NOTIFICATION_LOG') !== null
        ? filter_var(env('DISPATCH_DEBUG_NOTIFICATION_LOG'), FILTER_VALIDATE_BOOLEAN)
        : $defaultDebugNotify,

    /**
     * Fallback khi chưa có bản ghi `dispatch_settings` (hoặc migrate chưa chạy).
     * Hành khách: mặc định ~3 ngày; hàng hoá: 24 giờ.
     */
    'passenger_urgent_threshold_hours' => (int) env('DISPATCH_PASSENGER_URGENT_HOURS', 72),
    'cargo_urgent_threshold_hours' => (int) env('DISPATCH_CARGO_URGENT_HOURS', 24),

    'notifications_queue_default' => env('DISPATCH_NOTIFICATIONS_QUEUE_DEFAULT', 'default'),
    'notifications_queue_urgent' => env('DISPATCH_NOTIFICATIONS_QUEUE_URGENT', 'urgent-notifications'),

    /** Số ngày tới sinh sẵn phiếu lặp trong `dispatch:materialize-recurring-requests`. */
    'recurring_materialization_horizon_days' => max(1, (int) env('DISPATCH_RECURRING_HORIZON_DAYS', 21)),

    /** Footer email chờ duyệt trưởng đơn vị (mailto hoặc chuỗi hiển thị). */
    'mail_helpdesk' => env('DISPATCH_MAIL_HELPDESK', ''),

    /** Sau bao nhiêu giờ kể từ fill giá mà gửi nhắc Trưởng BP (cron dispatch:remind-dept-approvals). */
    'dept_approval_reminder_after_hours' => max(1, (int) env('DISPATCH_DEPT_APPROVAL_REMIND_AFTER_HOURS', 24)),

    /** Sau bao nhiêu giờ kể từ lần cập nhật phiếu approved mà bắt đầu nhắc tải scan (cron dispatch:remind-signed-paper-upload). */
    'signed_paper_reminder_after_hours' => max(1, (int) env('DISPATCH_SIGNED_PAPER_REMIND_AFTER_HOURS', 24)),

    /** Gợi ý bảng giá trên chi tiết phiếu / wizard (GET /reference-pricing/suggest). */
    'pricing_suggest_enabled' => filter_var(env('DISPATCH_PRICING_SUGGEST_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /** OCR paper_scan qua queue (false = xử lý đồng bộ như trước). */
    'ocr_use_queue' => filter_var(env('DISPATCH_OCR_USE_QUEUE', false), FILTER_VALIDATE_BOOLEAN),

    /** Queue xử lý OCR + chữ ký bản signed_paper. */
    'document_processing_queue' => env('DISPATCH_DOCUMENT_QUEUE', 'document-processing'),

    /** OCR signed document qua queue (mặc định true). */
    'signed_document_use_queue' => filter_var(env('DISPATCH_SIGNED_DOCUMENT_USE_QUEUE', true), FILTER_VALIDATE_BOOLEAN),

    'signed_upload' => [
        'max_mb' => max(1, (int) env('DISPATCH_SIGNED_UPLOAD_MAX_MB', 10)),
        'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
    ],

    'signature_detection' => [
        'enabled' => filter_var(env('DISPATCH_SIGNATURE_DETECTION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'auto_pass_min_score' => (float) env('DISPATCH_SIGNATURE_AUTO_PASS_MIN_SCORE', 0.75),
        'manual_review_min_score' => (float) env('DISPATCH_SIGNATURE_MANUAL_REVIEW_MIN_SCORE', 0.35),
        /** ROI BM.03 section F — x, y, w, h as fraction of image width/height */
        'roi_zones' => [
            ['role' => 'procurement_head', 'x' => 0.02, 'y' => 0.72, 'w' => 0.30, 'h' => 0.22],
            ['role' => 'requester', 'x' => 0.35, 'y' => 0.72, 'w' => 0.30, 'h' => 0.22],
            ['role' => 'unit_head', 'x' => 0.68, 'y' => 0.72, 'w' => 0.30, 'h' => 0.22],
        ],
    ],

    /** Nếu true, markPaperReceived từ chối khi verification chưa pass (mặc định false = chỉ cảnh báo). */
    'paper_received_requires_verified' => filter_var(env('DISPATCH_PAPER_RECEIVED_REQUIRES_VERIFIED', false), FILTER_VALIDATE_BOOLEAN),
];
