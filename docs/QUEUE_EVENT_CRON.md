# QUEUE_EVENT_CRON — VA Điều Vận

## 📑 Mục lục

- [Queue connections](#queue-connections)
- [Jobs (custom)](#jobs-custom)
- [Notifications như tác vụ nền](#notifications-như-tác-vụ-nền)
- [Events & listeners](#events--listeners)
- [Artisan commands](#artisan-commands)
- [Scheduler (cron)](#scheduler-cron)
- [Retry & failed jobs](#retry--failed-jobs)
- [Idempotency](#idempotency)
- [Performance](#performance)

---

## Queue connections

Cấu hình: [config/queue.php](../config/queue.php).

| Driver | Khi nào |
|--------|---------|
| `sync` | Local dev (`.env.example` mặc định) — **không** cần worker |
| `database` | Cần `php artisan queue:work` + bảng `jobs` |
| `redis` | Production khuyến nghị |

**Failed jobs:** driver `database-uuids`, bảng `failed_jobs`.

---

## Jobs (custom)

Thư mục `app/Jobs/` **không chứa job class** trong repo hiện tại — hàng đợi nền chủ yếu qua **Notification implements ShouldQueue**.

---

## Notifications như tác vụ nền

| Class | Queue | Ghi chú |
|-------|-------|---------|
| `TripAssignedNotification` | `default` hoặc `urgent-notifications` | `ShouldQueue` + `ShouldQueueAfterCommit` |
| `NewDispatchRequestNotification` | Theo cờ urgent | `ShouldQueue` + `ShouldQueueAfterCommit`; `database` + **mail** khi `is_urgent` |
| `CargoSlaBreachedNotification` | default constructor | `ShouldQueue` — broadcast qua `Notification::send` trong command; `toArray()` trả `title`, `body`, `event`, `url` |
| `DispatchPackageSessionsLowBalanceNotification` | `notifications_queue_default` | `ShouldQueue` + `ShouldQueueAfterCommit` — cảnh báo gần hết buổi trong gói |
| `DeptHeadApprovalRequestedNotification` | `notifications_queue_default` | `ShouldQueue` + `ShouldQueueAfterCommit`; `database` + **mail** |
| `DeptHeadDecisionNotification` | `notifications_queue_default` | `ShouldQueue` + `ShouldQueueAfterCommit`; `database` + **mail** (người đề xuất) |
| `DeptApprovalReminderNotification` | `notifications_queue_default` | `ShouldQueue` + `ShouldQueueAfterCommit`; cron `dispatch:remind-dept-approvals` |
| `TripAssignedNotification` | default hoặc urgent | `ShouldQueue` + `ShouldQueueAfterCommit`; `database` + **mail** (tài xế) |

Queue name lấy từ `config/dispatch.php`:

- `notifications_queue_default`
- `notifications_queue_urgent`

---

## Events & listeners

Đăng ký trong [app/Providers/EventServiceProvider.php](../app/Providers/EventServiceProvider.php):

| Event | Listener |
|-------|----------|
| `Illuminate\Auth\Events\Registered` | `SendEmailVerificationNotification` |
| `Illuminate\Notifications\Events\NotificationSent` | `SendWebPushOnDatabaseNotification` |

**Flow Web Push:** Sau khi notification được gửi qua channel `database`, listener gọi `WebPushSender` nếu VAPID đã cấu hình.

> **Auto-discovery:** `shouldDiscoverEvents()` trả về `false` — chỉ các mapping trên được load.

---

## Artisan commands

| Command | Mục đích |
|---------|-----------|
| `cargo:sla-check` | Quét `cargo_shipments` quá SLA → `audit_logs` + notify dispatcher/admin |
| `cargo:backfill-shipments` | Tạo shipment cho yêu cầu cargo đã duyệt có trip nhưng thiếu phiếu |
| `dispatch:materialize-recurring-requests` | Sinh `dispatch_requests` từ templates lặp (recurring CLB…) |
| `cms:sync-users` | Đồng bộ user từ DB CMS (`cms` connection) |
| `inspire` | Mặc định Laravel (demo) |

---

## Scheduler (cron)

[app/Console/Kernel.php](../app/Console/Kernel.php):

```php
$schedule->command('cargo:sla-check')->everyFiveMinutes();
$schedule->command('dispatch:materialize-recurring-requests')->hourly();
$schedule->command('dispatch:remind-dept-approvals')->dailyAt('08:00');
```

**Yêu cầu vận hành:** crontab gọi `php artisan schedule:run` **mỗi phút**.

---

## Retry & failed jobs

- Notification queue: `ShouldQueue` dùng retry mặc định Laravel / `$tries` nếu override trong class (hiện không override trong các file đã đọc — dùng default worker `--tries`).
- Worker Supervisor: đặt `--tries=3` (ví dụ trong DEPLOYMENT_GUIDE).

```bash
php artisan queue:failed
php artisan queue:retry all
```

---

## Idempotency

Middleware `idempotency` trên các POST nhạy cảm (`dispatch-requests`, `trips assign`, `trip-costs decision`, …).

**DB:** `idempotent_requests` lưu `(user_id, scope, key_hash)` và response — tránh double-submit mạng lag.

---

## Performance

- Ưu tiên **Redis queue** khi lượng notify lớn.
- Tách queue `urgent-notifications` để worker ưu tiên (cần nhiều worker hoặc xử lý fair scheduling — tuỳ cấu hình infra).
- `TripAssignedNotification` **after commit** giảm race khi transaction rollback.

---

## Liên quan

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)
