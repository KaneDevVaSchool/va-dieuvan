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
];
