<?php

return [
    /**
     * Fallback khi chưa có bản ghi `dispatch_settings` (hoặc migrate chưa chạy).
     * Hành khách: mặc định ~3 ngày; hàng hoá: 24 giờ.
     */
    'passenger_urgent_threshold_hours' => (int) env('DISPATCH_PASSENGER_URGENT_HOURS', 72),
    'cargo_urgent_threshold_hours' => (int) env('DISPATCH_CARGO_URGENT_HOURS', 24),

    'notifications_queue_default' => env('DISPATCH_NOTIFICATIONS_QUEUE_DEFAULT', 'default'),
    'notifications_queue_urgent' => env('DISPATCH_NOTIFICATIONS_QUEUE_URGENT', 'urgent-notifications'),
];
