# Spike: Laravel Broadcasting + Echo (thay / bổ sung poll)

Mục tiêu: admin & driver nhận tín hiệu ngay khi `TripOpsController::updateStatus` hoặc `TripCostController::decide` hoàn tất, thay vì chỉ dựa poll (`useVisiblePoll`).

## 1. Phía Laravel

- Bật `BROADCAST_DRIVER` (Redis / Pusher / Reverb tùy hạ tầng).
- Định nghĩa **event** implements `ShouldBroadcast` (hoặc `ShouldBroadcastNow` cho latency thấp), ví dụ:
  - `TripStatusUpdated` — payload tối thiểu: `trip_id`, `status`, `from`, `to`, `updated_at`.
  - `TripCostDecided` — `trip_cost_id`, `trip_id`, `status`.
- Channel gợi ý:
  - `private-trips.{tripId}` — user đã auth subscribe khi mở chi tiết chuyến.
  - `private-dispatch` hoặc `private-user.{id}` — dashboard list refetch (cân nhắc tải).
- **Authorization**: trong `routes/channels.php`, chỉ user có `TripVisibility::userCanViewTrip` mới join `trips.{id}`.

## 2. Phía SPA (`resources/js`)

- Cài `laravel-echo` + driver (`pusher-js` hoặc `@laravel/echo` + Reverb websocket).
- Trong `bootstrap.js` (hoặc entry admin tách): khởi tạo `Echo` với URL key từ `.env` (`VITE_PUSHER_APP_KEY`, v.v.).
- **TripDetailView**: `onMounted` subscribe `private-trips.${id}`, nghe `.TripStatusUpdated` → `load({ silent: true })`; `onUnmounted` leave channel.
- **TripsListView / DriverDashboard**: subscribe rộng cần cân bằng tải — có thể chỉ refetch khi nhận event có `trip_id` thuộc filter hiện tại (hoặc giữ poll chậm làm safety net).

## 3. So với poll hiện tại

| Cách | Ưu | Nhược |
|------|----|--------|
| Poll | Không cần server broadcast | Độ trễ theo chu kỳ |
| Echo | Gần realtime | Cấu hình hạ tầng + auth channel |

Khuyến nghị: triển khai Echo cho `TripStatusUpdated` trước; giữ poll 50–60s như fallback khi disconnect.

## 4. Việc cần làm tiếp (checklist devops)

- [ ] Chọn driver (Reverb self-host vs Pusher).
- [ ] Thêm env production + TLS ws/wss.
- [ ] Load test số channel khi nhiều tab mở.
