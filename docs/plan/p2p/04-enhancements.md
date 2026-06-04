# 04 — Đề xuất Nâng cấp & Mở rộng Tính năng

> Các tính năng không nằm trong scope MVP nhưng được thiết kế để dễ tích hợp sau.  
> Mỗi đề xuất có: mô tả, giá trị nghiệp vụ, độ phức tạp, và điều kiện tiên quyết.

---

## Tier 1 — Nâng cấp ngắn hạn (sau MVP 1-3 tháng)

### ENH-01 — Thông báo phụ huynh chủ động

**Hiện tại:** Không có kênh thông tin trực tiếp đến phụ huynh.

**Đề xuất:** Khi driver đánh dấu học sinh đã lên xe (`boarded`) → gửi SMS/Zalo tự động cho phụ huynh:
> *"Học sinh Nguyễn Văn A đã lên xe chuyến sáng lúc 06:32. Dự kiến đến trường 07:15."*

Khi học sinh xuống xe → thông báo thêm.

**Giá trị:** Giảm cuộc gọi hỏi thăm của phụ huynh vào điều vận. Tăng tin tưởng với dịch vụ.

**Độ phức tạp:** Trung bình — cần tích hợp SMS gateway hoặc Zalo OA.

**Điều kiện:** `parent_phone` trong `tp_students`. Tích hợp SMS/Zalo provider.

**Schema bổ sung:**
```sql
-- Thêm vào tp_trip_student_logs
parent_notified_at TIMESTAMP,
parent_notify_status VARCHAR(20),  -- sent, delivered, failed
parent_notify_channel VARCHAR(20), -- sms, zalo
```

---

### ENH-02 — Báo cáo chi phí xuất Excel/PDF

**Hiện tại:** Chi phí chỉ xem được trong tab Cost trên web.

**Đề xuất:** Export báo cáo chi phí tháng/quý theo định dạng:
- Excel: breakdown per day, per program, per driver
- PDF: tóm tắt với chart

**Giá trị:** Kế toán cần báo cáo để thanh toán tài xế và quyết toán.

**Độ phức tạp:** Thấp — thêm 1 controller + Excel template.

---

### ENH-03 — Dashboard tổng quan đa chương trình

**Hiện tại:** Mỗi program có tab riêng. Không có view "hôm nay tất cả chương trình đang chạy thế nào".

**Đề xuất:** Màn hình `/transport-overview` — realtime dashboard:
- Card mỗi chương trình đang active: headcount, in_progress/completed/pending
- SSE live updates cho tất cả executions hôm nay
- Map driver đang ở đâu (nếu tích hợp GPS — xem ENH-07)

**Giá trị:** Điều vận có 1 màn hình duy nhất để monitor toàn bộ hoạt động.

**Độ phức tạp:** Thấp (UI) đến Trung bình (nếu có GPS).

---

### ENH-04 — Template chương trình

**Hiện tại:** Mỗi chương trình phải tạo từ đầu.

**Đề xuất:** Cho phép "Lưu làm template" từ 1 program đã tồn tại. Khi tạo program mới → có option "Tạo từ template" → pre-fill form với settings cũ (trừ dates và code).

**Giá trị:** Đầu năm học mới, tái sử dụng cấu hình chương trình cũ, chỉ đổi dates.

**Độ phức tạp:** Thấp.

**Schema bổ sung:**
```sql
-- Thêm vào tp_programs
template_id UUID REFERENCES tp_programs(id),  -- NULL nếu tạo từ đầu
is_template BOOLEAN DEFAULT false,
```

---

### ENH-05 — Enroll học sinh theo nhóm/lớp

**Hiện tại:** Enroll từng học sinh hoặc search chọn thủ công.

**Đề xuất:** Enroll theo filter: "Enroll toàn bộ học sinh lớp 1A" hoặc "Toàn bộ Khối 1".

**Giá trị:** Đầu năm học, có thể enroll 500 học sinh chính sách trong 2 click.

**Độ phức tạp:** Thấp — thêm bulk enroll endpoint với filter.

```
POST /api/tp-programs/:id/enrollments/bulk-by-filter
body: { grade: "Khối 1", campus_id: 1 }
→ Enroll tất cả tp_students match filter chưa enrolled
```

---

## Tier 2 — Nâng cấp trung hạn (3-6 tháng)

### ENH-06 — Lịch học tích hợp (School Calendar integration)

**Hiện tại:** Hệ thống mới không có `school_calendars` (bỏ từ thiết kế cũ). Program chỉ dùng `runs_on` và `excluded_dates`.

**Vấn đề:** Admin phải manually thêm ngày nghỉ lễ vào `excluded_dates` cho từng chương trình.

**Đề xuất:** Khôi phục bảng `school_calendars` (hoặc dùng bảng từ hệ thống khác). Khi generate program_days, tự động skip ngày có `day_type = 'holiday'` trong lịch trường.

**Giá trị:** Admin chỉ quản lý lịch nghỉ 1 lần, tất cả programs tự động theo.

**Độ phức tạp:** Trung bình — cần tích hợp `school_calendars` vào `ProgramDayGeneratorService`.

**Schema:**
```sql
CREATE TABLE school_calendars (
    id          UUID PRIMARY KEY,
    school_year VARCHAR(9),         -- "2026-2027"
    date        DATE UNIQUE,
    day_type    VARCHAR(20) CHECK (day_type IN ('school_day','holiday','weekend','makeup_day')),
    note        VARCHAR(255),
    created_by  BIGINT REFERENCES users(id)
);
```

**Logic update:**
```php
// ProgramDayGeneratorService
$dates = $dates->filter(fn($d) =>
    !SchoolCalendar::isHoliday($d)  // skip holidays
    || $program->extra_dates->contains($d)  // except manual extra_dates
);
```

---

### ENH-07 — GPS tracking thời gian thực

**Hiện tại:** Điều vận không biết xe đang ở đâu.

**Đề xuất:** Driver app gửi location mỗi 30 giây khi execution `in_progress`. Dispatcher thấy trên map.

**Giá trị:** Điều vận biết xe đến nơi chưa, xử lý sự cố kịp thời.

**Độ phức tạp:** Cao — cần WebSocket hoặc MQTT, lưu GPS timeseries, map component.

**Schema bổ sung:**
```sql
CREATE TABLE tp_trip_location_pings (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    execution_id    UUID NOT NULL REFERENCES tp_trip_executions(id),
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    accuracy        DECIMAL(5,1),
    speed_kmh       DECIMAL(5,1),
    recorded_at     TIMESTAMP NOT NULL,
    created_at      TIMESTAMP DEFAULT NOW()
);
CREATE INDEX idx_tp_location_execution ON tp_trip_location_pings(execution_id, recorded_at DESC);
```

**Điều kiện:** App driver native (React Native) có GPS access. Backend WebSocket support.

---

### ENH-08 — App phụ huynh (Parent App)

**Hiện tại:** Phụ huynh báo vắng qua điện thoại cho điều vận.

**Đề xuất:** Phụ huynh có app/web riêng:
- Xem lịch đưa đón của con
- Báo vắng self-service (tự động ghi `tp_day_absences` với `source = 'parent'`)
- Nhận thông báo khi con lên/xuống xe

**Giá trị:** Giảm tải điều vận, tăng trải nghiệm phụ huynh.

**Độ phức tạp:** Cao — cần authentication riêng cho phụ huynh, UI/UX mới.

**Schema bổ sung:**
```sql
-- Liên kết phụ huynh với học sinh
CREATE TABLE parent_student_links (
    id          UUID PRIMARY KEY,
    parent_id   BIGINT REFERENCES users(id),
    student_id  UUID REFERENCES tp_students(id),
    relationship VARCHAR(30),  -- 'father', 'mother', 'guardian'
    is_primary  BOOLEAN DEFAULT false,
    created_at  TIMESTAMP DEFAULT NOW()
);
```

---

### ENH-09 — Multi-stop route (nhiều điểm đón/trả)

**Hiện tại:** Chương trình chỉ có 1 origin và 1 destination.

**Đề xuất:** Cho phép define route với nhiều stops (waypoints). Mỗi học sinh được assign vào 1 stop. Driver app hiển thị theo thứ tự stops, biết stop nào có bao nhiêu học sinh.

**Giá trị:** Phục vụ các trường có nhiều khu dân cư, học sinh tập kết tại nhiều điểm.

**Độ phức tạp:** Cao — thay đổi data model đáng kể.

**Schema bổ sung:**
```sql
CREATE TABLE tp_program_stops (
    id          UUID PRIMARY KEY,
    program_id  UUID REFERENCES tp_programs(id),
    stop_name   VARCHAR(255),
    address     TEXT,
    latitude    DECIMAL(10,7),
    longitude   DECIMAL(10,7),
    stop_order  INTEGER,
    eta_offset  INTEGER,        -- phút từ departure_time
    created_at  TIMESTAMP
);

-- Thêm vào tp_enrollments
pickup_stop_id  UUID REFERENCES tp_program_stops(id),
dropoff_stop_id UUID REFERENCES tp_program_stops(id),
```

---

### ENH-10 — Approval workflow cho thay đổi lớn

**Hiện tại:** Admin có thể cancel chương trình, unenroll hàng loạt học sinh không cần approve.

**Đề xuất:** Các thay đổi có tác động lớn cần approval:
- Cancel chương trình đang active → require School Admin approve
- Bulk unenroll > 10 học sinh → require approval
- Thay đổi default driver → notify và require confirm

**Giá trị:** Kiểm soát thay đổi, tránh lỗi human error.

**Độ phức tạp:** Trung bình.

**Schema bổ sung:**
```sql
CREATE TABLE tp_change_requests (
    id              UUID PRIMARY KEY,
    entity_type     VARCHAR(50),
    entity_id       UUID,
    change_type     VARCHAR(60),
    requested_by    BIGINT REFERENCES users(id),
    approved_by     BIGINT REFERENCES users(id),
    status          VARCHAR(20) CHECK (status IN ('pending','approved','rejected')),
    change_payload  JSONB,
    notes           TEXT,
    created_at      TIMESTAMP,
    resolved_at     TIMESTAMP
);
```

---

## Tier 3 — Nâng cấp dài hạn (6-12 tháng)

### ENH-11 — Analytics & Insights

**Đề xuất:** Module báo cáo nâng cao:

```
Dashboard Analytics:
├── Attendance rate theo chương trình / tuần / tháng (trend chart)
├── Top 10 học sinh vắng nhiều nhất
├── Tài xế có completion rate cao nhất
├── Chi phí per-student theo chương trình
├── Ngày có tỷ lệ vắng cao bất thường (anomaly detection)
└── Dự báo headcount ngày tiếp theo (dựa trên pattern lịch sử)
```

**Độ phức tạp:** Cao — cần data warehouse hoặc materialized views.

---

### ENH-12 — API tích hợp hệ thống ngoài

**Đề xuất:** REST API public để tích hợp với:
- Hệ thống quản lý học sinh của trường (auto-sync `tp_students`)
- ERP tài chính (push cost data)
- Hệ thống chấm công tài xế

**Schema bổ sung:**
```sql
CREATE TABLE tp_api_integrations (
    id              UUID PRIMARY KEY,
    name            VARCHAR(100),
    type            VARCHAR(30),    -- 'student_sync', 'cost_export', 'driver_timesheet'
    endpoint_url    TEXT,
    api_key_hash    VARCHAR(255),
    sync_frequency  VARCHAR(30),    -- 'realtime', 'hourly', 'daily'
    last_synced_at  TIMESTAMP,
    status          VARCHAR(20),
    settings        JSONB,
    created_at      TIMESTAMP
);
```

---

### ENH-13 — Seasonal program cloning

**Đề xuất:** Clone toàn bộ chương trình sang năm học mới:
- Copy program settings, default driver, cost
- Shift date range +1 năm
- Giữ nguyên danh sách học sinh enrolled (exclude graduated)
- Sinh lịch mới tự động

**Giá trị:** Đầu năm học mới, setup trong vài phút thay vì tạo lại từ đầu.

**Độ phức tạp:** Thấp về logic, trung bình về UX (wizard).

---

### ENH-14 — Seat assignment & capacity management

**Đề xuất:** Mỗi học sinh có số ghế cố định. Khi vehicle capacity < enrollment count → cảnh báo và gợi ý tách chuyến hoặc đổi xe.

**Giá trị:** Đảm bảo an toàn, tránh xe quá tải.

**Schema bổ sung:**
```sql
-- Thêm vào tp_enrollments
seat_number VARCHAR(10),    -- "A1", "B3" (optional)

-- Thêm vào tp_program_days
capacity_status VARCHAR(20) CHECK (capacity_status IN ('ok','warning','exceeded')),
```

---

### ENH-15 — AI-powered absence prediction

**Đề xuất:** Dựa trên lịch sử, dự báo học sinh nào có khả năng vắng cao vào ngày X (thứ 2 đầu tuần, trước/sau lễ). Dispatcher nhận gợi ý trước khi ngày đó đến.

**Giá trị:** Dispatcher chủ động liên hệ phụ huynh xác nhận trước, giảm absent no_notice.

**Độ phức tạp:** Rất cao — cần ML model, đủ data để train (minimum 1 năm học).

**Điều kiện:** ENH-11 phải có trước để có đủ historical data.

---

## Tổng hợp Roadmap

```
MVP (Hiện tại)
├── Core: Program + Day + Enrollment + Absence (sparse)
├── Driver: Execution + Student Logs + Offline sync
├── Import: 5-step enterprise pipeline
├── Cost: Estimated + Actual per execution
└── Reports: Absence pivot + Cost monthly

Tier 1 (1-3 tháng sau MVP)
├── ENH-01: Thông báo phụ huynh (SMS/Zalo)
├── ENH-02: Export báo cáo chi phí
├── ENH-03: Dashboard tổng quan đa chương trình
├── ENH-04: Template chương trình
└── ENH-05: Enroll theo nhóm/lớp

Tier 2 (3-6 tháng)
├── ENH-06: School Calendar tích hợp
├── ENH-07: GPS tracking
├── ENH-08: App phụ huynh
├── ENH-09: Multi-stop route
└── ENH-10: Approval workflow

Tier 3 (6-12 tháng)
├── ENH-11: Analytics & Insights
├── ENH-12: API tích hợp ngoài
├── ENH-13: Seasonal program cloning
├── ENH-14: Seat assignment
└── ENH-15: AI absence prediction
```

---

## Ghi chú thiết kế: Những tính năng cố tình KHÔNG làm

Để giữ hệ thống đơn giản và tập trung, các tính năng sau đã được xem xét và quyết định **không triển khai**:

| Tính năng | Lý do không làm |
|-----------|-----------------|
| Tự động assign driver | Điều vận cần kiểm soát, không nên để hệ thống tự quyết định |
| Auto-cancel khi toàn bộ HS vắng | Có thể driver vẫn cần chạy (chờ ở điểm đón, học sinh xuất hiện muộn) |
| Realtime bidirectional chat | Scope quá lớn, dùng điện thoại thông thường là đủ |
| Tính KPI tài xế từ attendance data | Nhạy cảm về HR, cần policy rõ trước khi tự động hóa |
| Dynamic pricing per student | Chính sách học sinh là miễn phí, không phù hợp context |
