# Database Indexes & Performance

## Indexes hiện có (từ migrations)

### trips
- `trips.dispatch_request_id` — FK lookup
- `trips.driver_id` — Driver trip listing
- `trips.vehicle_id` — Vehicle availability
- `trips.status` — Status filtering
- `trips.depart_at` — Date range queries
- `trips.payment_status` — Payment filtering

### dispatch_requests
- `dispatch_requests.requester_id` — User requests
- `dispatch_requests.status` — Status filtering
- `dispatch_requests.depart_at` — Date range
- `dispatch_requests.dept_head_id` — Dept head queue
- `dispatch_requests.recurring_parent_id` — Recurring lookup

### tp_program_days
- `(program_id, scheduled_date)` — Composite UNIQUE
- `program_id` — Program day listing

### tp_enrollments
- `(program_id, student_id)` — Composite
- `enrolled_at`, `unenrolled_at` — Date range filtering

### tp_day_absences
- `(program_day_id, student_id, shift)` — Composite UNIQUE

### audit_logs
- `(auditable_type, auditable_id)` — Polymorphic lookup
- `actor_id` — User audit trail
- `created_at` — Time range

### notifications
- `(notifiable_type, notifiable_id, read_at)` — Standard Laravel

## Khuyến nghị bổ sung indexes

### Ưu tiên CAO (impact trực tiếp đến performance)

```sql
-- 1. trips: tìm kiếm tài xế theo range ngày (N+1 trong DriverWorkloadService)
CREATE INDEX idx_trips_driver_depart
    ON trips (driver_id, depart_at)
    WHERE status NOT IN ('cancelled', 'incident');

-- 2. tp_trip_student_logs: lookup theo execution
CREATE INDEX idx_tp_student_logs_execution_student
    ON tp_trip_student_logs (execution_id, student_id);

-- 3. dispatch_requests: full-text search origin/destination
-- Nếu dùng MySQL 8.0+:
ALTER TABLE dispatch_requests
    ADD FULLTEXT INDEX ft_origin_dest (origin, destination);

-- 4. audit_logs: query theo event type + time range
CREATE INDEX idx_audit_event_created
    ON audit_logs (event, created_at);

-- 5. notifications: unread count (thường xuyên query)
CREATE INDEX idx_notif_notifiable_unread
    ON notifications (notifiable_id, notifiable_type, read_at)
    WHERE read_at IS NULL;
```

### Ưu tiên TRUNG BÌNH

```sql
-- 6. tp_enrollments: active enrollments on a date
CREATE INDEX idx_tp_enroll_active
    ON tp_enrollments (program_id, enrolled_at, unenrolled_at);

-- 7. trips: stats query (join với dispatch_requests)
CREATE INDEX idx_trips_status_type
    ON trips (status, transport_provider_id);

-- 8. idempotent_requests: TTL cleanup
CREATE INDEX idx_idempotent_created
    ON idempotent_requests (created_at);
```

## Queries có nguy cơ N+1

### 1. AttendanceService::enrollmentsForDay()
- ✅ Đã dùng `with('student')` — OK

### 2. AttendanceService::boardedAtMapForDay()
- ⚠️ `$execution->studentLogs()->get()` — Nếu nhiều execution sẽ N+1
- Giải pháp: eager load khi fetch program day

### 3. TripController::index() stats query
- ⚠️ Clone query nhiều lần với different WHERE clauses
- Giải pháp: Một query với conditional aggregation

### 4. DriverWorkloadController
- ⚠️ Cần kiểm tra có lazy-load trip list không

## Query chậm cần theo dõi

```sql
-- Truy vấn trips list với nhiều filters và joins
EXPLAIN SELECT trips.*, ...
FROM trips
LEFT JOIN dispatch_requests ON ...
WHERE trips.status IN (...)
  AND trips.depart_at BETWEEN ? AND ?
ORDER BY trips.depart_at DESC
LIMIT 20;

-- Expected: sử dụng index idx_trips_driver_depart hoặc idx_trips_status_type
```
