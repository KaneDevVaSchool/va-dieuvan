# Module: Thông báo (Notifications)

## Mô tả

Hệ thống thông báo đa kênh: database (in-app inbox) + Web Push (mobile/desktop).

## Kênh thông báo

| Kênh | Mô tả |
|------|-------|
| `database` | Lưu vào bảng `notifications`, hiển thị trong inbox |
| `broadcast` | Realtime qua WebSocket (nếu configured) |
| `mail` | Email qua SMTP |
| Web Push | Push notification qua VAPID (minishlink/web-push) |

## Flow thông báo

```
Sự kiện xảy ra (trip assigned, request approved, ...)
    │
Laravel Notification dispatched
    │
    ├── via(['database'])  → notifications table
    │                      → Listener: SendWebPushOnDatabaseNotification
    │                                  │
    │                                  └── WebPushSender → VAPID Push API
    │
    └── via(['mail'])  → Mailable → SMTP
```

## Danh sách Notifications

| Class | Trigger | Kênh |
|-------|---------|------|
| `TripAssignedNotification` | Chuyến được gán cho tài xế | DB + Push + Email |
| `TripCancelledNotification` | Chuyến bị hủy | DB + Push + Email |
| `TripRescheduledNotification` | Chuyến đổi lịch | DB + Push + Email |
| `TripDepartureReminderNotification` | Nhắc trước giờ chạy | DB + Push |
| `TripDriverRemovedNotification` | Tài xế bị gỡ khỏi chuyến | DB + Push |
| `DeptHeadApprovalRequestedNotification` | Cần trưởng đơn vị duyệt | DB + Email |
| `DeptHeadDecisionNotification` | Trưởng đơn vị đã quyết định | DB + Email |
| `DeptApprovalReminderNotification` | Nhắc duyệt hàng ngày | DB + Email |
| `NewDispatchRequestNotification` | Yêu cầu mới đến | DB |
| `SignedPaperUploadReminderNotification` | Nhắc upload biên bản | DB + Email |
| `TpDriverAssignmentNotification` | Tài xế được gán vào TP | DB + Push |
| `TpDriverMorningReminderNotification` | Nhắc ca sáng TP | DB + Push |
| `TpDriverAfternoonReminderNotification` | Nhắc ca chiều TP | DB + Push |
| `CargoSlaBreachedNotification` | Hàng hóa quá SLA | DB |
| `RecurringBudgetExceededNotification` | Vượt ngân sách định kỳ | DB |
| `DispatchPackageSessionsLowBalanceNotification` | Sắp hết số buổi | DB |

## Web Push Setup

```env
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:admin@example.com
```

Tài xế subscribe push notification từ `/driver` app.

## API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/notifications` | Inbox (paginated) |
| PUT | `/api/notifications/{id}/read` | Đánh dấu đã đọc |
| PUT | `/api/notifications/read-all` | Đọc tất cả |
| POST | `/api/push-subscriptions` | Đăng ký push |
| DELETE | `/api/push-subscriptions` | Hủy push |
| GET | `/api/nav-badges` | Badge counts (unread count) |

## Known Issues

- Notifications chưa implement `ShouldQueue` — gửi synchronously trong request (có thể chậm)
- `NavBadgesService` dùng polling thay vì realtime push
