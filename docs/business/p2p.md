# TASK 3.4 — Module P2P Học Sinh Chính Sách

> **FR-P01** · SRS v2.0 · Đã cải thiện từ draft v0.1  
> Loại thay đổi: **Module mới** · Ưu tiên: **P1**  
> Cập nhật: 04/06/2026 — Giải quyết 4 lỗi critical, 5 gap logic, 5 đề xuất nâng cấp

---

## Mục Lục

1. [Bối Cảnh & Mục Tiêu](#1-bối-cảnh--mục-tiêu)
2. [Phạm Vi Module](#2-phạm-vi-module)
3. [Data Model](#3-data-model)
4. [Business Logic — Tự Động Sinh Chuyến](#4-business-logic--tự-động-sinh-chuyến)
5. [Luồng Tài Xế — Mobile App](#5-luồng-tài-xế--mobile-app)
6. [Luồng Gán Tài Xế — Điều Vận](#6-luồng-gán-tài-xế--điều-vận)
7. [Xử Lý Học Sinh Vắng](#7-xử-lý-học-sinh-vắng)
8. [Dashboard Điều Vận](#8-dashboard-điều-vận)
9. [Acceptance Criteria](#9-acceptance-criteria)
10. [API Endpoints](#10-api-endpoints)
11. [Notification & Alert](#11-notification--alert)
12. [Edge Cases — Đã Quyết Định](#12-edge-cases--đã-quyết-định)
13. [Dependencies & Risks](#13-dependencies--risks)
14. [Definition of Done](#14-definition-of-done)
15. [Changelog từ v0.1](#15-changelog-từ-v01)

---

## 1. Bối Cảnh & Mục Tiêu

### 1.1 Vấn đề hiện tại

Một nhóm học sinh ("học sinh policy") được hưởng dịch vụ đưa đón nội bộ miễn phí giữa các cơ sở trường hàng ngày. Điều vận đang tạo phiếu thủ công mỗi sáng và chiều — lặp đi lặp lại với cùng danh sách học sinh, cùng tuyến, cùng khung giờ.

### 1.2 Mục tiêu module

| Mục tiêu | Đo lường |
|---|---|
| Loại bỏ tạo phiếu thủ công hàng ngày | Số phiếu thủ công = 0 trong ngày học |
| Tài xế thực hiện điểm danh lên/xuống xe | 100% chuyến policy có log điểm danh |
| Điều vận theo dõi được chuyến policy theo ngày/tuần | Dashboard hiển thị đủ dữ liệu |
| Không sinh chuyến vào ngày nghỉ/lễ | 0 chuyến được tạo sai ngày |

---

## 2. Phạm Vi Module

### Trong phạm vi (In scope)

- Quản lý danh sách học sinh policy
- Tự động sinh chuyến theo lịch ngày học
- Luồng gán tài xế/xe cho chuyến policy
- Tài xế điểm danh lên/xuống xe trên Mobile App (offline-capable)
- Xử lý học sinh vắng mặt
- Dashboard Điều vận theo dõi chuyến policy realtime
- Tích hợp lịch nghỉ/lễ của trường

### Ngoài phạm vi (Out of scope)

- Thanh toán / chi phí (chuyến policy miễn phí)
- Tuyến có hành khách hỗn hợp (policy + non-policy)
- Thay đổi tuyến mid-semester ngoài quy trình admin

---

## 3. Data Model

> **Quyết định thiết kế:** Tất cả soft-delete dùng `status = 'inactive'` + `deleted_at`, không xóa vật lý để giữ toàn vẹn lịch sử `policy_trip_students`.

### 3.1 Bảng `student_policies`

```sql
student_policies
├── id                  UUID PK
├── student_id          FK → students.id
├── route_id            FK → routes.id
├── school_year         VARCHAR(9)               -- "2025-2026"
├── semester            TINYINT                  -- 1 | 2
├── time_slot           ENUM('morning','afternoon')  -- 1 bản ghi / ca (xem E1)
├── pickup_point_id     FK → stops.id
├── dropoff_point_id    FK → stops.id
├── effective_from      DATE
├── effective_to        DATE
├── status              ENUM('active','inactive','suspended')
├── deleted_at          TIMESTAMP NULL           -- [THÊM MỚI] soft delete marker
├── created_by          FK → users.id
├── created_at          TIMESTAMP
└── updated_at          TIMESTAMP

-- Constraints
UNIQUE (student_id, route_id, time_slot, semester, school_year)
  WHERE deleted_at IS NULL                       -- [THÊM MỚI] tránh trùng policy
```

> **Lưu ý E1:** Học sinh có policy cả 2 ca → tạo **2 bản ghi riêng**, mỗi bản ghi 1 `time_slot`. UNIQUE constraint đảm bảo không trùng lặp.

### 3.2 Bảng `policy_trips`

```sql
policy_trips
├── id                  UUID PK
├── trip_date           DATE
├── time_slot           ENUM('morning','afternoon')
├── route_id            FK → routes.id
├── driver_id           FK → drivers.id NULL     -- NULL khi mới sinh, Điều vận gán sau
├── vehicle_id          FK → vehicles.id NULL
├── status              ENUM('scheduled','assigned','in_progress','completed','cancelled')
│                                                -- [SỬA] thêm 'assigned'
├── planned_departure   TIME                     -- 06:00 | 15:30
├── actual_departure    TIMESTAMP NULL
├── actual_arrival      TIMESTAMP NULL
├── expected_count      SMALLINT DEFAULT 0       -- [THÊM MỚI] đếm HS dự kiến
├── boarded_count       SMALLINT DEFAULT 0       -- [THÊM MỚI] đếm HS đã lên xe
├── absent_count        SMALLINT DEFAULT 0       -- [THÊM MỚI] đếm HS vắng
├── route_snapshot      JSONB NULL               -- [THÊM MỚI] snapshot config route tại thời điểm sinh
├── generated_at        TIMESTAMP
├── generated_by        ENUM('system','manual')
├── cancelled_at        TIMESTAMP NULL
├── cancelled_by        FK → users.id NULL
├── cancel_reason       TEXT NULL
└── notes               TEXT NULL

-- Constraints
UNIQUE (trip_date, time_slot, route_id)          -- [THÊM MỚI] phòng duplicate khi job chạy đồng thời
```

### 3.3 Bảng `policy_trip_students`

```sql
policy_trip_students
├── id                  UUID PK
├── policy_trip_id      FK → policy_trips.id
├── student_id          FK → students.id
├── student_policy_id   FK → student_policies.id -- giữ FK; student_policies chỉ soft delete
├── expected            BOOLEAN DEFAULT true
├── boarded_at          TIMESTAMP NULL
├── boarded_by          FK → users.id NULL        -- [THÊM MỚI] ai tích lên xe
├── alighted_at         TIMESTAMP NULL
├── alighted_by         FK → users.id NULL        -- [THÊM MỚI] ai tích xuống xe
├── absence_reason      ENUM('absent_reported','absent_no_notice','late_cancellation') NULL
├── reported_by         FK → users.id NULL
└── updated_at          TIMESTAMP
```

### 3.4 Bảng `school_calendars`

```sql
school_calendars
├── id                  UUID PK
├── school_year         VARCHAR(9)
├── semester            TINYINT NULL             -- [THÊM MỚI] 1 | 2 | NULL (ngày nghỉ)
├── date                DATE UNIQUE
├── day_type            ENUM('school_day','holiday','weekend','makeup_day')
├── note                VARCHAR(255) NULL
└── created_by          FK → users.id
```

> **[CRITICAL FIX - L1]** Thêm cột `semester` vào `school_calendars`. Job sinh chuyến tra cứu `semester` trực tiếp từ đây — không dùng hàm tính ngầm định. Admin import lịch phải điền `semester` cho tất cả `school_day` và `makeup_day`.

### 3.5 Bảng `policy_trip_audit` _(mới)_

```sql
policy_trip_audit
├── id                  UUID PK
├── policy_trip_id      FK → policy_trips.id
├── changed_by          FK → users.id
├── changed_at          TIMESTAMP DEFAULT NOW()
├── action              ENUM('created','assigned_driver','status_changed',
│                            'student_marked_absent','student_boarded',
│                            'student_alighted','cancelled')
├── old_value           JSONB NULL
└── new_value           JSONB NULL
```

---

## 4. Business Logic — Tự Động Sinh Chuyến

### 4.1 Điều kiện sinh chuyến

Hệ thống sinh chuyến cho ngày `D` khi **tất cả** điều kiện sau thỏa mãn:

```
1. school_calendars[D].day_type IN ('school_day', 'makeup_day')
2. school_calendars[D].semester IS NOT NULL           -- [THÊM] validate đủ data
3. Tồn tại ít nhất 1 student_policy có:
   - status = 'active'
   - effective_from <= D <= effective_to
   - semester = school_calendars[D].semester
   - deleted_at IS NULL
4. Chưa tồn tại policy_trip cho (D, time_slot, route_id)
   → Kiểm tra bằng UNIQUE constraint, INSERT ... ON CONFLICT DO NOTHING
```

### 4.2 Lịch chạy Job

```
Cron Job: generate_policy_trips
├── PRIMARY:  Mỗi ngày lúc 22:00 (T-1)    -- sinh chuyến cho ngày hôm sau
├── FALLBACK: 05:00 (cùng ngày T)           -- sinh bù nếu job T-1 fail
├── Idempotent: INSERT ... ON CONFLICT (trip_date, time_slot, route_id) DO NOTHING
└── Alert: Nếu cả 2 lần fail → gửi email Admin ngay lập tức
```

**[CRITICAL FIX - L2] — Xử lý race condition:**

```sql
-- Dùng INSERT ... ON CONFLICT thay vì SELECT-then-INSERT
INSERT INTO policy_trips (trip_date, time_slot, route_id, status, planned_departure,
                          expected_count, generated_at, generated_by, route_snapshot)
VALUES (:trip_date, :time_slot, :route_id, 'scheduled', :planned_departure,
        :student_count, NOW(), 'system', :route_json)
ON CONFLICT (trip_date, time_slot, route_id) DO NOTHING;
```

**Ví dụ:** Lúc 22:00 Chủ nhật, job sinh 2 chuyến cho thứ Hai:
- `morning` · Route Thông Tây Hội → Bình Thới · 06:00
- `afternoon` · Route Bình Thới → Thông Tây Hội · 15:30

### 4.3 Sinh danh sách học sinh

Sau khi tạo `policy_trip`, INSERT `policy_trip_students` cho tất cả học sinh thỏa:

```sql
SELECT sp.*
FROM student_policies sp
WHERE sp.route_id       = :route_id
  AND sp.time_slot      = :time_slot
  AND sp.status         = 'active'
  AND sp.deleted_at     IS NULL
  AND sp.effective_from <= :trip_date
  AND sp.effective_to   >= :trip_date
  AND sp.semester       = :semester              -- [SỬA] lấy từ school_calendars[D].semester

-- Insert với expected = true
INSERT INTO policy_trip_students (policy_trip_id, student_id, student_policy_id, expected)
SELECT :trip_id, sp.student_id, sp.id, true
FROM (...query trên...)
ON CONFLICT DO NOTHING;
```

### 4.4 Đồng bộ danh sách khi policy thay đổi

**[CRITICAL FIX - L3]** Khi `student_policy.status` → `inactive` hoặc `suspended` sau khi chuyến đã được sinh:

```sql
-- Trigger: fn_sync_policy_trip_students
-- Kích hoạt: AFTER UPDATE ON student_policies
-- Điều kiện: NEW.status IN ('inactive', 'suspended') AND OLD.status = 'active'

UPDATE policy_trip_students pts
SET expected        = false,
    absence_reason  = 'absent_reported',
    reported_by     = :changed_by,
    updated_at      = NOW()
WHERE pts.student_policy_id = NEW.id
  AND pts.expected  = true
  AND EXISTS (
    SELECT 1 FROM policy_trips pt
    WHERE pt.id = pts.policy_trip_id
      AND pt.status IN ('scheduled', 'assigned')  -- chưa bắt đầu
      AND pt.trip_date >= CURRENT_DATE
  );

-- Cập nhật expected_count
UPDATE policy_trips
SET expected_count = (
    SELECT COUNT(*) FROM policy_trip_students
    WHERE policy_trip_id = policy_trips.id AND expected = true
)
WHERE id IN (SELECT DISTINCT policy_trip_id FROM policy_trip_students
             WHERE student_policy_id = NEW.id);
```

### 4.5 Xử lý ngày nghỉ

| Tình huống | Hành vi hệ thống |
|---|---|
| Ngày nghỉ lễ trong `school_calendars` | Không sinh chuyến |
| Thêm ngày nghỉ sau khi chuyến đã sinh | Điều vận hủy thủ công + ghi lý do (hệ thống gợi ý nhưng không tự hủy) |
| `makeup_day` (kể cả rơi thứ 7 — xem E7) | Sinh chuyến bình thường, không cần confirm riêng |
| `school_calendars[D].semester = NULL` | Job skip + ghi warning log, alert Admin |
| Chuyến manual đã tồn tại cùng (date, time_slot, route_id) | Job skip qua ON CONFLICT. **Không merge** danh sách HS — Điều vận tự xử lý trên chuyến manual. |

---

## 5. Luồng Tài Xế — Mobile App

> Bổ sung màn hình điểm danh học sinh cho loại chuyến `policy`. Offline-capable với queue sync.

### 5.1 Sequence Flow

```
[Tài xế] Mở app → Thấy chuyến policy đã được giao (status: assigned)
    │
    │  [Nếu chưa có tài xế: status = scheduled, app không hiển thị chuyến này]
    ↓
[1] Nhận chuyến → status: assigned (do Điều vận gán — xem Section 6)
    ↓
[2] Bấm "Bắt đầu chuyến" → actual_departure = NOW(), status: in_progress
    ↓
[3] Màn hình danh sách học sinh
    │  - Hiển thị tất cả HS có expected = true
    │  - HS đã được Điều vận đánh dấu vắng trước → hiển thị "Vắng (đã báo)", không cần tích
    │
    ├── Tích "✓ Đã lên xe"     → boarded_at = NOW(), boarded_by = driver_user_id
    │    (tích lần lượt từng em)
    │
    ├── [Tuỳ chọn] Đánh dấu vắng → chọn lý do từ dropdown
    │    absence_reason: 'absent_no_notice' | 'late_cancellation'
    │
    └── Confirm danh sách → tiếp tục di chuyển
    ↓
[4] Đến điểm đến — Màn hình tích xuống xe
    │
    └── Tích "✓ Đã xuống xe"   → alighted_at = NOW(), alighted_by = driver_user_id
    ↓
[5] Bấm "Hoàn thành chuyến"
    │  → actual_arrival = NOW(), status: completed
    │  → System kiểm tra: còn HS nào boarded_at NOT NULL mà alighted_at NULL?
    │    Nếu có → tạo alert gửi Điều vận (xem Section 11)
    └──────────────────────────────────────────────────────
```

### 5.2 Quy tắc validation trên app

| Điều kiện | Hành vi |
|---|---|
| Bấm "Hoàn thành" khi còn HS chưa tích xuống (và boarded) | Cảnh báo danh sách tên cụ thể, yêu cầu xác nhận tiếp tục |
| Bấm "Hoàn thành" khi còn HS chưa xử lý (không tích lên, không đánh vắng) | Block — bắt buộc xử lý hết mới cho phép complete |
| Mất kết nối mạng | Queue offline (IndexedDB), sync khi có mạng. Timestamp lấy từ device clock |
| Chuyến chưa được assign tài xế (status = scheduled) | Không hiển thị trong app tài xế |

---

## 6. Luồng Gán Tài Xế — Điều Vận

> **[MỚI - L6]** Luồng này chưa được mô tả trong v0.1, bổ sung đầy đủ.

### 6.1 Khi nào gán tài xế

- Hệ thống sinh chuyến với `driver_id = NULL` (không tự động gán)
- Điều vận gán thủ công qua dashboard sau khi chuyến được tạo
- Có thể gán bất cứ lúc nào khi `status IN ('scheduled', 'assigned')`
- Không thể thay đổi tài xế khi `status = 'in_progress'`

### 6.2 Validation khi gán

```
Trước khi PATCH /api/policy-trips/:id/assign-driver, hệ thống kiểm tra:

1. Tài xế có status = 'available' (hoặc chính sách tương đương)
2. [E2] Tài xế không được giao chuyến khác cùng time_slot + trip_date:
   SELECT COUNT(*) FROM policy_trips
   WHERE driver_id = :driver_id
     AND trip_date = :trip_date
     AND time_slot = :time_slot
     AND status NOT IN ('cancelled')
   → COUNT > 0: trả lỗi 409 "Tài xế đã có chuyến khác trong khung giờ này"

3. Xe (vehicle_id) đủ chỗ cho expected_count học sinh
   → vehicle.capacity < expected_count: cảnh báo (không block)
```

### 6.3 Notification sau khi gán

Sau khi gán tài xế thành công:
- `policy_trips.status` → `'assigned'`
- Gửi push notification cho tài xế (xem Section 11)
- Chuyến hiển thị trên app tài xế

---

## 7. Xử Lý Học Sinh Vắng

### 7.1 Các kênh báo vắng

**Kênh 1 — Phụ huynh/GVCN báo trước (qua Điều vận):**
```
Phụ huynh / GVCN → Điều vận → Điều vận đánh dấu vắng trên web portal
expected = true (giữ nguyên), absence_reason = 'absent_reported'
→ Trigger cập nhật: expected_count không đổi, absent_count tăng
→ Tài xế thấy học sinh được highlight "Vắng có phép" — không cần tích
```

**Kênh 2 — Tài xế phát hiện tại chỗ:**
```
Tài xế không tích được học sinh → Đánh dấu vắng trên app
absence_reason = 'absent_no_notice'
→ Trigger notification realtime đến Điều vận (xem Section 11)
```

**Kênh 3 — Hủy muộn:**
```
Báo vắng trong vòng [cancel_threshold] phút trước giờ xe
absence_reason = 'late_cancellation'
→ Trigger notification đến Điều vận

[CONFIG] cancel_threshold: mặc định 30 phút, có thể cấu hình per-school
         Lưu trong bảng school_settings hoặc env config
```

### 7.2 Logic khi toàn bộ học sinh vắng

```
IF policy_trip.expected_count > 0
   AND COUNT(policy_trip_students WHERE expected=true AND absence_reason IS NOT NULL)
       = policy_trip.expected_count
   AND policy_trip.status IN ('scheduled', 'assigned')
THEN
   Gửi in-app alert đến Điều vận:
   "Toàn bộ học sinh chuyến [Ca] [Tuyến] ngày [D] đã báo vắng. Có muốn hủy chuyến?"
   → Điều vận xác nhận thủ công
   → Không tự động hủy
```

---

## 8. Dashboard Điều Vận

### 8.1 View "Chuyến Policy theo ngày"

**Filters:**
- Ngày (date picker, mặc định = hôm nay)
- Ca: Sáng / Chiều / Tất cả
- Tuyến
- Trạng thái chuyến

**Columns:**

| Cột | Mô tả | Realtime? |
|---|---|---|
| Ca | Sáng / Chiều | — |
| Tuyến | Tên route | — |
| Giờ dự kiến | 06:00 / 15:30 | — |
| Tài xế | Tên + SĐT · hoặc badge "Chưa gán" | — |
| Xe | Biển số | — |
| HS dự kiến | `expected_count` | — |
| HS đã lên | `boarded_count` | ✓ SSE |
| HS vắng | `absent_count` | ✓ SSE |
| Trạng thái | scheduled / assigned / in_progress / completed / cancelled | ✓ SSE |
| Hành động | Xem chi tiết · Gán tài xế · Hủy | — |

> **[MỚI - U5]** Cột "HS đã lên", "HS vắng", và "Trạng thái" cập nhật realtime qua **Server-Sent Events (SSE)**. Endpoint: `GET /api/policy-trips/live-updates?date=&time_slot=`. Fallback: polling 30s nếu browser không hỗ trợ SSE.

### 8.2 View "Chi tiết chuyến"

Khi Điều vận click "Xem chi tiết":

```
GET /api/policy-trips/:id/students

Hiển thị danh sách học sinh với:
- Họ tên, lớp
- Trạng thái: Dự kiến / Đã lên xe (timestamp) / Đã xuống xe (timestamp) / Vắng (lý do)
- Người báo vắng (nếu có)
```

### 8.3 View "Danh sách học sinh policy"

Quản lý master list — thêm/sửa/ngưng policy từng học sinh.

**Fields quản lý:**

| Field | Ghi chú |
|---|---|
| Họ tên học sinh | |
| Lớp / Cơ sở | |
| Tuyến được giao | |
| Khung giờ | Sáng / Chiều — 1 dòng per ca (xem E1) |
| Điểm đón / Điểm trả | |
| Hiệu lực từ → đến | |
| Trạng thái | Active / Inactive / Suspended |
| Ghi chú | |

**Khi thay đổi status → inactive/suspended:** Hệ thống hiển thị cảnh báo: _"Thay đổi này sẽ ảnh hưởng đến X chuyến chưa thực hiện. Tiếp tục?"_ (xem Section 4.4).

### 8.4 View "Báo cáo vắng theo tuần"

Bảng pivot: **Học sinh (hàng)** × **Ngày trong tuần (cột)**

```
Học sinh       | T2    | T3    | T4    | T5    | T6    | Tổng | Có phép | Không phép
---------------|-------|-------|-------|-------|-------|------|---------|------------
Nguyễn Văn A   |  ✓   |  ✓   |  ✗V  |  ✓   |  ✓   |  1   |    1    |     0
Trần Thị B     |  ✓   |  ✗N  |  ✓   |  ✓   |  ✓   |  1   |    0    |     1

Legend: ✓ Có mặt · ✗V Vắng có phép (absent_reported) · ✗N Vắng không phép (absent_no_notice)
        ✗L Hủy muộn (late_cancellation)
```

**Filter:** Tuần · Tuyến · Ca · Lớp

---

## 9. Acceptance Criteria

### AC1 — Tự động sinh chuyến

- [ ] Job 22:00 sinh đủ chuyến sáng + chiều cho ngày học hôm sau
- [ ] Job dùng `INSERT ... ON CONFLICT DO NOTHING` — không tạo duplicate dù chạy nhiều lần
- [ ] Không sinh chuyến khi `school_calendars[D].semester IS NULL` — log warning + alert Admin
- [ ] Danh sách HS trong chuyến khớp với `student_policies` active tại ngày đó (đúng semester)
- [ ] Log ghi: thời điểm sinh, số HS, `generated_by = 'system'`
- [ ] `route_snapshot` lưu đủ thông tin route tại thời điểm sinh

### AC2 — Tài xế điểm danh

- [ ] Chỉ hiển thị chuyến có `status = 'assigned'` trên app tài xế
- [ ] Tài xế thấy HS được Điều vận đánh vắng trước — hiển thị trạng thái rõ ràng, không cần tích lại
- [ ] Tích lên/xuống xe ghi `timestamp` + `boarded_by` / `alighted_by` chính xác
- [ ] Tích vắng bắt buộc chọn lý do
- [ ] Không thể "Hoàn thành" khi còn HS chưa xử lý (chưa tích lên, chưa đánh vắng) — **block hoàn toàn**
- [ ] Cảnh báo nếu có HS đã lên nhưng chưa tích xuống — **cảnh báo + xác nhận, không block**
- [ ] Offline queue hoạt động đúng, sync khi có mạng

### AC3 — Gán tài xế

- [ ] Điều vận gán tài xế/xe cho chuyến `scheduled` hoặc `assigned`
- [ ] Hệ thống block gán nếu tài xế đã có chuyến khác cùng ca cùng ngày (E2)
- [ ] Sau khi gán: status → `assigned`, tài xế nhận push notification
- [ ] Không cho phép thay đổi tài xế khi chuyến đang `in_progress`

### AC4 — Điều vận theo dõi

- [ ] Dashboard hiển thị tất cả chuyến policy theo ngày đã chọn
- [ ] Filter theo ca / tuyến / trạng thái hoạt động đúng
- [ ] `boarded_count`, `absent_count`, `status` cập nhật realtime qua SSE
- [ ] View tuần hiển thị đủ 5 ngày học, phân biệt 3 loại vắng

### AC5 — Xử lý vắng

- [ ] Điều vận đánh vắng trước → tài xế thấy HS đó đã được đánh dấu, không cần tích lại
- [ ] Tài xế đánh vắng tại chỗ → Điều vận nhận in-app notification realtime
- [ ] Alert toàn bộ vắng gửi đúng thời điểm (trước `cancel_threshold` phút)

### AC6 — Không sinh chuyến ngày nghỉ

- [ ] `day_type = 'holiday' | 'weekend'` → không có chuyến được tạo
- [ ] `makeup_day` (kể cả thứ 7) → chuyến sinh bình thường
- [ ] Job fallback 05:00 không sinh chuyến cho ngày đã được đánh dấu nghỉ

### AC7 — Đồng bộ policy thay đổi

- [ ] Inactive/suspend một policy → tự động update `expected=false` cho các chuyến `scheduled`/`assigned` tương lai
- [ ] `expected_count` được cập nhật đúng sau thay đổi
- [ ] Điều vận thấy cảnh báo số chuyến bị ảnh hưởng trước khi xác nhận thay đổi

---

## 10. API Endpoints

### 10.1 Admin / Điều vận — Web Portal

```
# Policy Trips
GET    /api/policy-trips
       ?date=&time_slot=&route_id=&status=
GET    /api/policy-trips/:id
GET    /api/policy-trips/:id/students          -- [THÊM MỚI - L9]
PATCH  /api/policy-trips/:id/cancel
       body: { reason: string }
PATCH  /api/policy-trips/:id/assign-driver
       body: { driver_id, vehicle_id }
GET    /api/policy-trips/live-updates          -- [THÊM MỚI - SSE endpoint]
       ?date=&time_slot=

# Student Policies
GET    /api/student-policies?semester=&status=&route_id=
POST   /api/student-policies
PATCH  /api/student-policies/:id
DELETE /api/student-policies/:id               -- soft delete: deleted_at = NOW()

# School Calendars
GET    /api/school-calendars?year=&month=
POST   /api/school-calendars/bulk              -- import lịch theo học kỳ (với semester)
PATCH  /api/school-calendars/:date

# Reports
GET    /api/policy-trips/absence-report
       ?week_start=&route_id=&time_slot=&class=
```

**Validation rules cho `POST /api/student-policies`:**
```
- student_id: required, FK valid
- route_id: required, FK valid, route phải tồn tại và active
- time_slot: required, 'morning' | 'afternoon'
- semester + school_year: required
- effective_from <= effective_to
- UNIQUE check: không trùng (student_id, route_id, time_slot, semester, school_year)
  khi status = 'active'
```

### 10.2 Tài Xế — Mobile App

```
GET    /api/driver/policy-trips?date=today
       → Chỉ trả về chuyến có status = 'assigned' và driver_id = current_user

GET    /api/driver/policy-trips/:id/students
       → Danh sách HS, trạng thái hiện tại (expected, boarded_at, alighted_at, absence_reason)

POST   /api/driver/policy-trips/:id/start
       → Validate: status phải = 'assigned'
       → Set actual_departure, status = 'in_progress'

POST   /api/driver/policy-trips/:id/complete
       → Validate: tất cả HS phải đã được xử lý (boarded hoặc absent)
       → Set actual_arrival, status = 'completed'
       → Trigger check: HS boarded mà chưa alighted → tạo alert

PATCH  /api/driver/policy-trip-students/:id/board
       → Set boarded_at = NOW(), boarded_by = current_user_id
       → Increment policy_trips.boarded_count

PATCH  /api/driver/policy-trip-students/:id/alight
       → Set alighted_at = NOW(), alighted_by = current_user_id

PATCH  /api/driver/policy-trip-students/:id/absent
       body: { absence_reason: 'absent_no_notice' | 'late_cancellation' }
       → Set absence_reason, expected = true (giữ nguyên), updated_at
       → Increment policy_trips.absent_count
       → Trigger notification Điều vận nếu absence_reason = 'absent_no_notice'
```

**Offline sync:** Tất cả PATCH/POST từ app hỗ trợ offline queue. Khi sync, server xử lý theo `updated_at` từ device — nếu conflict (ví dụ Điều vận đã xử lý trong khi tài xế offline), server-wins với log ghi rõ.

---

## 11. Notification & Alert

| Sự kiện | Người nhận | Kênh | Timing | Điều kiện |
|---|---|---|---|---|
| Chuyến được gán tài xế | Tài xế | Push + SMS | Ngay lập tức | `driver_id` vừa được set |
| HS vắng không phép (tài xế báo) | Điều vận | In-app + Email | Realtime | `absence_reason = 'absent_no_notice'` |
| HS đã lên xe nhưng chưa tích xuống khi complete | Điều vận | In-app alert | Ngay khi complete | `boarded_at NOT NULL AND alighted_at IS NULL` |
| Toàn bộ HS vắng — chuyến có thể hủy | Điều vận | In-app | `cancel_threshold` phút trước `planned_departure` | `absent_count >= expected_count` |
| Chuyến chưa được gán tài xế 2h trước giờ xe | Điều vận | In-app + Email | `planned_departure - 2h` | `driver_id IS NULL` |
| Job sinh chuyến thất bại (cả 2 lần) | Admin hệ thống | Email | Ngay sau lần fail thứ 2 | — |
| `school_calendars` thiếu semester | Admin hệ thống | Email | Khi job detect | `semester IS NULL` cho ngày học |
| Policy thay đổi ảnh hưởng chuyến tương lai | Điều vận | In-app | Khi Điều vận save thay đổi | Có ≥1 chuyến bị ảnh hưởng |

---

## 12. Edge Cases — Đã Quyết Định

| # | Tình huống | Quyết định |
|---|---|---|
| **E1** | HS có policy cả 2 ca | **2 bản ghi riêng** với UNIQUE (student_id, route_id, time_slot, semester, school_year). UI cho phép tạo 2 dòng từ cùng 1 màn hình. |
| **E2** | Tài xế được giao 2 tuyến cùng khung giờ | **Block** tại `assign-driver` API: kiểm tra conflict trước khi gán, trả lỗi 409 có tên chuyến xung đột. |
| **E3** | HS đổi tuyến giữa học kỳ | `effective_to` của bản ghi cũ = ngày đổi - 1. Tạo bản ghi mới với `effective_from` = ngày đổi. Không ảnh hưởng chuyến đã hoàn thành. |
| **E4** | Lịch thêm ngày nghỉ sau khi chuyến đã sinh | Hệ thống gửi in-app gợi ý hủy. **Điều vận xác nhận thủ công.** Không tự hủy. |
| **E5** | Không có tài xế khả dụng khi chuyến sinh | Sinh chuyến với `driver_id = NULL`, status = `scheduled`. Alert Điều vận (xem Section 11). |
| **E6** | HS chuyển trường mid-semester | Điều vận inactive policy. Trigger sync cập nhật `expected=false` cho chuyến tương lai. Chuyến đã completed không bị ảnh hưởng. |
| **E7** | Makeup day rơi thứ 7 | `day_type = 'makeup_day'` đủ điều kiện sinh chuyến. **Không cần confirm riêng.** Admin chịu trách nhiệm khi import lịch. |
| **E8** _(Mới)_ | Chuyến manual đã tồn tại cùng (date, time_slot, route_id) | Job skip qua `ON CONFLICT DO NOTHING`. **Không merge** danh sách HS. Điều vận quản lý chuyến manual riêng. |
| **E9** _(Mới)_ | Tài xế offline khi tích điểm danh, Điều vận chỉnh sửa cùng lúc | Server-wins khi sync: timestamp server ghi đè. Log audit ghi rõ nguồn (`boarded_by`). Điều vận nhận notification về conflict. |

---

## 13. Dependencies & Risks

### Dependencies

| Dependency | Mô tả | Owner | Deadline yêu cầu |
|---|---|---|---|
| `routes` | Tuyến phải active trước khi tạo policy | Ops | Trước sprint 1 |
| `school_calendars` | Import đủ cả `semester` trước khai giảng | Admin | T-7 ngày khai giảng |
| `drivers` / `vehicles` | Luồng gán tài xế/xe rõ ràng | Ops | Sprint 1 |
| Mobile App | Hỗ trợ màn hình điểm danh + offline queue | Mobile team | Sprint 2 |
| SSE infrastructure | Backend hỗ trợ Server-Sent Events | Backend | Sprint 2 |

### Risks

| Risk | Mức độ | Mitigation |
|---|---|---|
| `school_calendars` thiếu `semester` → job không sinh chuyến | **High** | Validation khi import + alert Admin khi detect thiếu |
| Race condition cron job | **High** | `UNIQUE constraint + ON CONFLICT DO NOTHING` — đã giải quyết |
| Tài xế offline lâu → conflict khi sync | **Medium** | Server-wins policy + audit log + notification Điều vận |
| Tài xế không quen luồng mới | **Medium** | Training + UI có hướng dẫn inline + UAT với 1 tài xế thực tế |
| Job cron fail silent | **Medium** | Health check + alert 2 lần (22:00 + 05:00) + monitoring dashboard |
| Policy thay đổi không được sync kịp | **Low** | Trigger tự động (Section 4.4) — đã giải quyết |
| Danh sách HS stale khi policy thay đổi sau khi sinh chuyến | **Low** | Trigger `fn_sync_policy_trip_students` — đã giải quyết |

---

## 14. Definition of Done

### Backend

- [ ] Unit tests coverage ≥ 80% cho business logic sinh chuyến (bao gồm test idempotency)
- [ ] Unit tests cho trigger `fn_sync_policy_trip_students`
- [ ] Integration test: job sinh chuyến đúng ngày học, skip ngày nghỉ, skip khi thiếu semester
- [ ] Integration test: conflict detection khi gán tài xế (E2)
- [ ] Migration script tạo đủ bảng mới, thêm columns mới, không phá dữ liệu hiện tại
- [ ] Migration script thêm UNIQUE constraint `policy_trips(trip_date, time_slot, route_id)`
- [ ] SSE endpoint hoạt động và có fallback polling
- [ ] Runbook cho trường hợp job fail

### Mobile App

- [ ] UI/UX màn hình điểm danh được review bởi ≥1 tài xế thực tế (UAT)
- [ ] Offline queue hoạt động đúng trong điều kiện mạng yếu / mất mạng
- [ ] Conflict resolution khi sync offline được test

### QA & Deployment

- [ ] Dashboard reviewed bởi Điều vận lead
- [ ] E2E test: toàn bộ luồng từ sinh chuyến → gán tài xế → tài xế điểm danh → complete
- [ ] Load test: job sinh 50+ chuyến đồng thời không tạo duplicate
- [ ] Document API cập nhật trong Postman collection (bao gồm SSE endpoint)
- [ ] Staging deploy và smoke test trước production

---

## 15. Changelog từ v0.1

| # | Loại | Mô tả |
|---|---|---|
| **[L1]** | 🐛 Critical fix | Thêm cột `semester` vào `school_calendars` — loại bỏ ambiguity ánh xạ DATE → semester |
| **[L2]** | 🐛 Critical fix | Thêm UNIQUE constraint + `ON CONFLICT DO NOTHING` — phòng race condition giữa 2 cron job |
| **[L3]** | 🐛 Critical fix | Thêm trigger `fn_sync_policy_trip_students` — tự động cập nhật danh sách HS khi policy thay đổi |
| **[L4]** | 🐛 Critical fix | Thêm `'assigned'` vào ENUM `policy_trips.status` — đồng bộ data model với sequence flow |
| **[L5]** | 📝 Decision | E1 (HS 2 ca): quyết định dùng 2 bản ghi riêng + UNIQUE constraint |
| **[L6]** | ✨ New section | Bổ sung toàn bộ Section 6 — Luồng gán tài xế (validation, conflict check, notification) |
| **[L7]** | 🐛 Logic fix | Notification chỉ gửi sau khi tài xế được gán (`driver_id NOT NULL`) |
| **[L8]** | ✨ Enhancement | `cancel_threshold` configurable per-school, không hardcode 30 phút |
| **[L9]** | ✨ New API | Thêm `GET /api/policy-trips/:id/students` cho Điều vận |
| **[U1]** | ✨ Enhancement | Thêm `boarded_by`, `alighted_by` vào `policy_trip_students` — audit trail |
| **[U2]** | ✨ Enhancement | Thêm `route_snapshot JSONB` vào `policy_trips` — snapshot config route tại thời điểm sinh |
| **[U3]** | ✨ Enhancement | Thêm `deleted_at` vào `student_policies` — đảm bảo toàn vẹn FK lịch sử |
| **[U4]** | ✨ Enhancement | Thêm `expected_count`, `boarded_count`, `absent_count` vào `policy_trips` — tránh JOIN nặng |
| **[U5]** | ✨ Enhancement | SSE endpoint cho dashboard realtime thay vì polling |
| **[E8]** | 📝 Decision | Xử lý chuyến manual đã tồn tại: job skip, không merge HS |
| **[E9]** | 📝 Decision | Conflict resolution khi sync offline: server-wins + audit log |
| **[NEW]** | ✨ New table | Thêm bảng `policy_trip_audit` — audit trail đầy đủ mọi thay đổi |

---

*Phiên bản: v2.0 · Cập nhật: 04/06/2026 · Sẵn sàng cho sprint planning*
