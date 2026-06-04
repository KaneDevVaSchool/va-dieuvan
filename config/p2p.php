<?php

/*
|--------------------------------------------------------------------------
| Module P2P — Học sinh chính sách (FR-P01, docs/p2p.md)
|--------------------------------------------------------------------------
| Cấu hình tập trung cho luồng tự động sinh chuyến đưa đón nội bộ, ngưỡng
| hủy muộn, mốc cảnh báo chuyến chưa gán tài xế và lịch chạy cron.
*/

return [
    /**
     * Giờ khởi hành dự kiến (planned_departure) theo ca.
     * Dùng khi sinh chuyến tự động (§4.2) và hiển thị trên dashboard.
     */
    'departure_times' => [
        'morning' => env('P2P_DEPARTURE_MORNING', '06:00'),
        'afternoon' => env('P2P_DEPARTURE_AFTERNOON', '15:30'),
    ],

    /**
     * Ngưỡng "hủy muộn" (§7.1 Kênh 3): báo vắng trong khoảng này (phút) trước
     * giờ xe được phân loại là `late_cancellation`. Cấu hình được per-school qua env.
     */
    'cancel_threshold_minutes' => max(1, (int) env('P2P_CANCEL_THRESHOLD_MINUTES', 30)),

    /**
     * Cảnh báo chuyến chưa gán tài xế: gửi khi còn <= số giờ này trước giờ xe (§11).
     */
    'unassigned_alert_lead_hours' => max(1, (int) env('P2P_UNASSIGNED_ALERT_LEAD_HOURS', 2)),

    /**
     * Lịch chạy job sinh chuyến (§4.2). PRIMARY chạy T-1 (sinh cho hôm sau),
     * FALLBACK chạy sáng cùng ngày để sinh bù nếu job T-1 fail.
     */
    'generation' => [
        'primary_at' => env('P2P_GENERATION_PRIMARY_AT', '22:00'),
        'fallback_at' => env('P2P_GENERATION_FALLBACK_AT', '05:00'),
    ],

    /**
     * SSE (§8.1, U5): thời lượng tối đa một kết nối stream (giây) và nhịp đẩy
     * cập nhật. Client tự reconnect; fallback polling khi không hỗ trợ EventSource.
     */
    'sse' => [
        'max_seconds' => max(10, (int) env('P2P_SSE_MAX_SECONDS', 60)),
        'tick_seconds' => max(1, (int) env('P2P_SSE_TICK_SECONDS', 3)),
        'poll_fallback_seconds' => max(5, (int) env('P2P_SSE_POLL_FALLBACK_SECONDS', 30)),
    ],
];
