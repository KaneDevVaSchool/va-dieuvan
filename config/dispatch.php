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

    /** Sinh bù chuyến P2P Policy trong N ngày tới (`policy:materialize-trips`). */
    'p2p_policy_horizon_days' => max(1, (int) env('P2P_POLICY_HORIZON_DAYS', 21)),

    /** Số ngày xử lý mỗi batch job khi kích hoạt kỳ P2P Policy. */
    'p2p_policy_activation_batch_days' => max(1, (int) env('P2P_POLICY_ACTIVATION_BATCH_DAYS', 7)),

    /** Tự tạo bản ghi students khi import mã HS chưa tồn tại. */
    'p2p_policy_auto_create_students' => filter_var(env('P2P_POLICY_AUTO_CREATE_STUDENTS', true), FILTER_VALIDATE_BOOLEAN),

    /** Gửi nhắc chuyến P2P trước giờ khởi hành (phút). */
    'p2p_policy_depart_reminder_lead_minutes' => max(1, (int) env('P2P_POLICY_DEPART_REMINDER_LEAD_MINUTES', 30)),

    /** Cho phép gửi nhắc nếu cron trễ sau depart_at (phút). */
    'p2p_policy_depart_reminder_grace_minutes' => max(0, (int) env('P2P_POLICY_DEPART_REMINDER_GRACE_MINUTES', 15)),

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
];
