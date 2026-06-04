# 01 — Kế hoạch Tổng thể

---

## 1. Domain Model

### 1.1 Các Aggregate chính

```
TransportProgram (tp_programs)
│   Root aggregate — "Chương trình đưa đón"
│   Owns: ProgramDay[], Enrollment[]
│
├── ProgramDay (tp_program_days)
│   │   Ngày vận hành — auto-generated từ date range
│   │   Immutable sau khi sinh (chỉ day_type và driver thay đổi)
│   │
│   └── TripExecution (tp_trip_executions)
│           Runtime — tạo ra khi driver bắt đầu chạy
│           Owns: TripStudentLog[]
│               └── TripStudentLog (tp_trip_student_logs)
│                       Điểm danh từng học sinh
│
├── Enrollment (tp_enrollments)
│       Ràng buộc học sinh ↔ chương trình
│
└── DayAbsence (tp_day_absences)
        Sparse exceptions: chỉ lưu khi học sinh VẮNG

Student (tp_students)
│   Standalone entity — tái sử dụng qua nhiều chương trình
│
└── ImportBatch (tp_import_batches)
        Session import
        └── ImportRow (tp_import_rows)

ProgramAuditLog (tp_audit_logs)
    Append-only, cross-entity
```

### 1.2 Nguyên tắc bất biến (Invariants)

1. `tp_program_days` không xóa — chỉ set `day_type = 'cancelled'`
2. `tp_enrollments` không xóa — chỉ set `unenrolled_at`
3. `tp_day_absences` chỉ lưu exceptions (vắng) — mặc định là có mặt
4. `tp_trip_executions` chỉ tạo khi driver bắt đầu — 1 day có tối đa 1 execution
5. `tp_audit_logs` append-only — không UPDATE, không DELETE
6. `student_snapshot` và `driver_snapshot` trong execution/logs là immutable JSON

---

## 2. Database Schema

### 2.1 `tp_programs`

```sql
CREATE TABLE tp_programs (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code                    VARCHAR(30) UNIQUE NOT NULL,
    name                    VARCHAR(255) NOT NULL,
    description             TEXT,

    origin_name             VARCHAR(255),
    destination_name        VARCHAR(255),
    origin_location_id      BIGINT REFERENCES locations(id),
    destination_location_id BIGINT REFERENCES locations(id),

    departure_time          TIME NOT NULL,
    return_time             TIME,
    start_date              DATE NOT NULL,
    end_date                DATE NOT NULL,
    runs_on                 JSONB NOT NULL DEFAULT '["mon","tue","wed","thu","fri"]',
    excluded_dates          DATE[] DEFAULT '{}',
    extra_dates             DATE[] DEFAULT '{}',

    -- Driver Layer: defaults cho toàn chương trình
    default_driver_id       BIGINT REFERENCES drivers(id),
    default_vehicle_id      BIGINT REFERENCES vehicles(id),

    -- Cost
    cost_per_trip           NUMERIC(12,2),
    cost_currency           VARCHAR(3) DEFAULT 'VND',
    cost_notes              TEXT,

    responsible_user_id     BIGINT REFERENCES users(id),
    status                  VARCHAR(20) NOT NULL DEFAULT 'draft'
                            CHECK (status IN ('draft','active','paused','completed','cancelled')),
    notes                   TEXT,
    settings                JSONB DEFAULT '{}',

    created_by              BIGINT REFERENCES users(id),
    created_at              TIMESTAMP DEFAULT NOW(),
    updated_at              TIMESTAMP DEFAULT NOW(),
    deleted_at              TIMESTAMP
);
```

### 2.2 `tp_program_days`

```sql
CREATE TABLE tp_program_days (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    program_id          UUID NOT NULL REFERENCES tp_programs(id) ON DELETE CASCADE,
    scheduled_date      DATE NOT NULL,
    day_type            VARCHAR(20) NOT NULL DEFAULT 'operating'
                        CHECK (day_type IN ('operating','cancelled','makeup')),
    expected_count      INTEGER NOT NULL DEFAULT 0,

    -- Driver Layer: override per-day (NULL = kế thừa từ tp_programs)
    driver_id           BIGINT REFERENCES drivers(id),
    vehicle_id          BIGINT REFERENCES vehicles(id),
    assigned_at         TIMESTAMP,
    assigned_by         BIGINT REFERENCES users(id),

    -- Cost: override per-day (NULL = kế thừa cost_per_trip)
    estimated_cost      NUMERIC(12,2),

    cancel_reason       TEXT,
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW(),

    UNIQUE (program_id, scheduled_date)
);

CREATE INDEX idx_tp_days_program_date ON tp_program_days(program_id, scheduled_date);
CREATE INDEX idx_tp_days_date         ON tp_program_days(scheduled_date);
CREATE INDEX idx_tp_days_driver       ON tp_program_days(driver_id) WHERE driver_id IS NOT NULL;
```

### 2.3 `tp_students`

```sql
CREATE TABLE tp_students (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code            VARCHAR(50) UNIQUE NOT NULL,
    full_name       VARCHAR(255) NOT NULL,
    grade           VARCHAR(20),
    class_name      VARCHAR(50),
    campus_id       BIGINT REFERENCES campuses(id),
    parent_name     VARCHAR(255),
    parent_phone    VARCHAR(20),
    address         TEXT,
    status          VARCHAR(20) NOT NULL DEFAULT 'active'
                    CHECK (status IN ('active','inactive','transferred','graduated')),
    metadata        JSONB DEFAULT '{}',
    source          VARCHAR(30) DEFAULT 'manual'
                    CHECK (source IN ('manual','excel_import','api_sync')),
    external_id     VARCHAR(100),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW(),
    deleted_at      TIMESTAMP
);
```

### 2.4 `tp_enrollments`

```sql
CREATE TABLE tp_enrollments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    program_id      UUID NOT NULL REFERENCES tp_programs(id),
    student_id      UUID NOT NULL REFERENCES tp_students(id),
    enrolled_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    enrolled_by     BIGINT REFERENCES users(id),
    unenrolled_at   TIMESTAMP,
    unenrolled_by   BIGINT REFERENCES users(id),
    unenroll_reason VARCHAR(255),
    notes           TEXT,
    UNIQUE (program_id, student_id)
);

CREATE INDEX idx_tp_enrollments_program
    ON tp_enrollments(program_id) WHERE unenrolled_at IS NULL;
```

### 2.5 `tp_day_absences`

```sql
CREATE TABLE tp_day_absences (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    program_day_id  UUID NOT NULL REFERENCES tp_program_days(id),
    student_id      UUID NOT NULL REFERENCES tp_students(id),
    absence_type    VARCHAR(30) NOT NULL DEFAULT 'absent'
                    CHECK (absence_type IN (
                        'absent',
                        'parent_notified',
                        'no_notice',
                        'late_cancel'
                    )),
    absence_reason  TEXT,
    recorded_by     BIGINT REFERENCES users(id),
    recorded_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    source          VARCHAR(20) DEFAULT 'dispatcher'
                    CHECK (source IN ('dispatcher','driver','system')),
    notes           TEXT,
    UNIQUE (program_day_id, student_id)
);
```

### 2.6 `tp_trip_executions`

```sql
CREATE TABLE tp_trip_executions (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    program_day_id      UUID NOT NULL REFERENCES tp_program_days(id),
    program_id          UUID NOT NULL REFERENCES tp_programs(id),

    -- Snapshot tại thời điểm start (immutable)
    driver_id           BIGINT NOT NULL REFERENCES drivers(id),
    vehicle_id          BIGINT REFERENCES vehicles(id),
    driver_snapshot     JSONB NOT NULL,
    vehicle_snapshot    JSONB,

    scheduled_time      TIME NOT NULL,
    started_at          TIMESTAMP,
    completed_at        TIMESTAMP,
    cancelled_at        TIMESTAMP,
    cancel_reason       TEXT,

    -- Cost
    estimated_cost      NUMERIC(12,2),
    actual_cost         NUMERIC(12,2),
    cost_notes          TEXT,
    cost_confirmed_by   BIGINT REFERENCES users(id),
    cost_confirmed_at   TIMESTAMP,

    -- Counters (denormalized)
    total_expected      SMALLINT DEFAULT 0,
    total_boarded       SMALLINT DEFAULT 0,
    total_alighted      SMALLINT DEFAULT 0,
    total_absent        SMALLINT DEFAULT 0,

    status              VARCHAR(20) NOT NULL DEFAULT 'in_progress'
                        CHECK (status IN ('in_progress','completed','cancelled')),

    device_id           VARCHAR(100),
    sync_version        INTEGER DEFAULT 1,

    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW(),

    UNIQUE (program_day_id)
);
```

### 2.7 `tp_trip_student_logs`

```sql
CREATE TABLE tp_trip_student_logs (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    execution_id        UUID NOT NULL REFERENCES tp_trip_executions(id) ON DELETE CASCADE,
    student_id          UUID NOT NULL REFERENCES tp_students(id),
    student_snapshot    JSONB NOT NULL,

    initial_status      VARCHAR(20) NOT NULL DEFAULT 'expected'
                        CHECK (initial_status IN ('expected','pre_absent')),

    boarded_at          TIMESTAMP,
    boarded_by          BIGINT REFERENCES users(id),
    alighted_at         TIMESTAMP,
    alighted_by         BIGINT REFERENCES users(id),
    absent_at           TIMESTAMP,
    absent_by           BIGINT REFERENCES users(id),
    absence_type        VARCHAR(30)
                        CHECK (absence_type IN (
                            'parent_notified','no_notice','late_cancel'
                        )),
    absence_notes       TEXT,

    final_status        VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (final_status IN (
                            'pending','boarded','alighted','absent'
                        )),

    client_timestamp    TIMESTAMP,
    sync_status         VARCHAR(20) DEFAULT 'synced'
                        CHECK (sync_status IN ('synced','pending_sync','conflict')),

    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW(),

    UNIQUE (execution_id, student_id)
);
```

### 2.8 `tp_import_batches`

```sql
CREATE TABLE tp_import_batches (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    original_filename   VARCHAR(255) NOT NULL,
    stored_path         VARCHAR(500) NOT NULL,
    file_size           INTEGER,
    file_type           VARCHAR(10) CHECK (file_type IN ('xlsx','xls','csv')),
    total_rows          INTEGER DEFAULT 0,
    header_row          JSONB,
    column_mapping      JSONB DEFAULT '{}',
    auto_fix_rules      JSONB DEFAULT '{}',
    valid_rows          INTEGER DEFAULT 0,
    warning_rows        INTEGER DEFAULT 0,
    error_rows          INTEGER DEFAULT 0,
    imported_rows       INTEGER DEFAULT 0,
    skipped_rows        INTEGER DEFAULT 0,
    status              VARCHAR(20) NOT NULL DEFAULT 'uploaded'
                        CHECK (status IN (
                            'uploaded','parsing','parsed',
                            'mapped','importing','completed','failed'
                        )),
    target_program_id   UUID REFERENCES tp_programs(id),
    error_report_path   VARCHAR(500),
    imported_by         BIGINT REFERENCES users(id),
    completed_at        TIMESTAMP,
    error_message       TEXT,
    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW()
);
```

### 2.9 `tp_import_rows`

```sql
CREATE TABLE tp_import_rows (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    batch_id            UUID NOT NULL REFERENCES tp_import_batches(id) ON DELETE CASCADE,
    row_number          INTEGER NOT NULL,
    raw_data            JSONB NOT NULL,
    mapped_data         JSONB,
    fixed_data          JSONB,
    validation_status   VARCHAR(10) NOT NULL DEFAULT 'pending'
                        CHECK (validation_status IN ('pending','valid','warning','error')),
    validation_errors   JSONB DEFAULT '[]',
    import_status       VARCHAR(20) DEFAULT 'pending'
                        CHECK (import_status IN ('pending','imported','skipped','failed')),
    student_id          UUID REFERENCES tp_students(id),
    import_error        TEXT,
    created_at          TIMESTAMP DEFAULT NOW()
);
```

### 2.10 `tp_audit_logs`

```sql
CREATE TABLE tp_audit_logs (
    id              BIGSERIAL PRIMARY KEY,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       UUID NOT NULL,
    program_id      UUID REFERENCES tp_programs(id),
    action          VARCHAR(60) NOT NULL,
    actor_id        BIGINT REFERENCES users(id),
    actor_name      VARCHAR(255),
    before_state    JSONB,
    after_state     JSONB,
    metadata        JSONB DEFAULT '{}',
    created_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_tp_audit_entity  ON tp_audit_logs(entity_type, entity_id);
CREATE INDEX idx_tp_audit_program ON tp_audit_logs(program_id, created_at DESC);
```

---

## 3. State Machines

### 3.1 TransportProgram

```
draft ──activate()──► active ──pause()──► paused ──resume()──► active
                                                                   │
                    auto: today > end_date                         │
                              ▼                                    │
                          completed                                │
                                                                   │
[any] ──cancel(reason)──► cancelled ◄──────────────────────────── ┘
```

### 3.2 ProgramDay

```
operating ──cancel_day(reason)──► cancelled
cancelled ──restore_day()──────► operating
(makeup tạo riêng, không auto-generate)
```

### 3.3 TripExecution (Driver view)

```
[program_day has driver assigned]
    │
    └─► Driver: "Bắt đầu" ──► in_progress
                                    │
                          [all students processed]
                                    │
                     Driver: "Hoàn thành" ──► completed
                                    │
                             cancel() ──► cancelled
```

### 3.4 TripStudentLog.final_status

```
pending
    ├──board()──► boarded
    │                 └──alight()──► alighted  ✓
    │
    └──absent()──► absent  ✓

Rule: "Hoàn thành" chỉ cho phép khi KHÔNG còn 'pending' hoặc 'boarded'
```

### 3.5 StudentImportBatch

```
uploaded ──parse──► parsing ──► parsed ──map──► mapped
                                                    │
                                              ──import──► importing ──► completed
                                                                   └──► failed
```

---

## 4. Business Flows chính

### Flow 1: Tạo chương trình + Sinh lịch

```
1. User fill form → POST /api/tp-programs
2. INSERT tp_programs (status = 'draft')
3. [SYNC] ProgramDayGeneratorService::generate()
   - Expand date range theo runs_on, excluded_dates, extra_dates
   - UPSERT tp_program_days (conflict on program_id, scheduled_date)
4. Audit: program.created + {day_count} ngày sinh
5. Response: program + day_count
```

### Flow 2: Thay đổi date range

```
1. PATCH /api/tp-programs/:id (start_date / end_date / excluded_dates thay đổi)
2. Compute: newSet - currentSet → INSERT thêm
3. Compute: currentSet - newSet:
   - Có data (absences/executions)? → SET day_type = 'cancelled'
   - Không có data?                 → DELETE ngày thừa
4. Audit: program.date_range_updated
```

### Flow 3: Enroll học sinh

```
1. POST /api/tp-programs/:id/enrollments
   body: { student_ids: [...] }
2. Validate: không trùng enrollment active
3. Batch INSERT tp_enrollments
4. UPDATE tp_program_days.expected_count += N (các ngày còn lại)
5. Audit: enrollment.bulk_enrolled {count}
```

### Flow 4: Xem attendance ngày X

```
GET /api/tp-program-days/:day_id/attendance
1. Fetch enrolled students (unenrolled_at IS NULL)
2. Fetch tp_day_absences (program_day_id = ?)
3. Merge: student + status = absences.has(id) ? 'absent' : 'attending'
4. Return list với effective_count
```

### Flow 5: Driver bắt đầu chuyến

```
POST /api/driver/tp-days/:day_id/start
1. Resolve effective_driver, effective_vehicle (day override ?? program default)
2. CREATE tp_trip_executions (snapshot driver, vehicle, estimated_cost)
3. Fetch enrolled students
4. Fetch pre-absences từ tp_day_absences
5. Seed tp_trip_student_logs:
   - pre_absent students: initial_status='pre_absent', final_status='absent'
   - others: initial_status='expected', final_status='pending'
6. Response: execution + student_logs[]
```

### Flow 6: Driver hoàn thành chuyến

```
POST /api/driver/tp-executions/:id/complete
1. Validate: COUNT(final_status = 'pending') = 0 → block nếu > 0
2. Warn: COUNT(final_status = 'boarded') > 0 → yêu cầu confirm
3. UPDATE tp_trip_executions SET completed_at, status = 'completed'
4. Alert dispatcher nếu có học sinh 'boarded' chưa 'alighted'
5. Audit: execution.completed
```

### Flow 7: Offline sync (driver)

```
POST /api/driver/tp-executions/:id/sync
body: { actions: [{action, student_id, client_timestamp, ...}] }
1. Sort actions by client_timestamp ASC
2. Apply từng action theo thứ tự (board/alight/absent)
3. Conflict: học sinh đã được dispatcher xử lý trong khi offline
   → Server state wins, ghi conflict log
   → Response: { conflicts: [{student_id, server_state, client_action}] }
4. Dispatcher nhận notification nếu có conflict
```

---

## 5. Permission Matrix

| Action | Super Admin | School Admin | Dispatcher | Driver | Accountant |
|--------|-------------|--------------|------------|--------|------------|
| Tạo/sửa/xóa chương trình | ✓ | ✓ | — | — | — |
| Xem danh sách chương trình | ✓ | ✓ | ✓ | — | ✓ (read) |
| Đăng ký / hủy học sinh | ✓ | ✓ | ✓ | — | — |
| Xem attendance ngày | ✓ | ✓ | ✓ | ✓ (của mình) | — |
| Đánh dấu vắng (dispatcher) | ✓ | ✓ | ✓ | — | — |
| Gán tài xế/xe per-day | ✓ | ✓ | ✓ | — | — |
| Bắt đầu/Hoàn thành chuyến | — | — | — | ✓ (của mình) | — |
| Đánh dấu vắng (driver) | — | — | — | ✓ (của mình) | — |
| Nhập chi phí thực tế | ✓ | ✓ | ✓ | — | ✓ |
| Import học sinh | ✓ | ✓ | — | — | — |
| Export báo cáo | ✓ | ✓ | ✓ | — | ✓ |
| Xem audit log | ✓ | ✓ | — | — | — |

---

## 6. API Design

### Programs

```
GET    /api/tp-programs                          List + filter
POST   /api/tp-programs                          Tạo + auto-generate days
GET    /api/tp-programs/:id                      Chi tiết
PATCH  /api/tp-programs/:id                      Cập nhật
DELETE /api/tp-programs/:id                      Soft delete

POST   /api/tp-programs/:id/activate
POST   /api/tp-programs/:id/pause
POST   /api/tp-programs/:id/cancel

GET    /api/tp-programs/:id/days                 List days (month filter)
GET    /api/tp-programs/:id/audit                Audit timeline
GET    /api/tp-programs/:id/reports/absence      Pivot báo cáo vắng
```

### Days

```
GET    /api/tp-program-days/:id                  Chi tiết ngày
PATCH  /api/tp-program-days/:id                  Cập nhật (notes, day_type)
PATCH  /api/tp-program-days/:id/assign-driver    Gán driver/vehicle override
DELETE /api/tp-program-days/:id/driver           Bỏ override, quay về default

GET    /api/tp-program-days/:id/attendance       DS học sinh + status ngày đó
```

### Enrollments

```
GET    /api/tp-programs/:id/enrollments          DS học sinh enrolled
POST   /api/tp-programs/:id/enrollments          Bulk enroll
DELETE /api/tp-programs/:id/enrollments/:sid     Unenroll
```

### Absences

```
POST   /api/tp-program-days/:id/absences         Mark absent (single/bulk)
DELETE /api/tp-program-days/:id/absences/:sid    Unmark absent
```

### Executions (Driver)

```
GET    /api/driver/tp-days                       Danh sách ngày hôm nay
GET    /api/driver/tp-days/:id                   Chi tiết ngày
POST   /api/driver/tp-days/:id/start             Bắt đầu → tạo execution
GET    /api/driver/tp-executions/:id/students    DS học sinh
PATCH  /api/driver/tp-executions/:id/students/:sid/board
PATCH  /api/driver/tp-executions/:id/students/:sid/alight
PATCH  /api/driver/tp-executions/:id/students/:sid/absent
POST   /api/driver/tp-executions/:id/complete
POST   /api/driver/tp-executions/:id/sync        Offline batch sync
```

### Cost

```
PATCH  /api/tp-executions/:id/cost               Cập nhật actual_cost
GET    /api/tp-programs/:id/reports/cost         Cost report theo tháng
```

### Students

```
GET    /api/tp-students                          List + search
POST   /api/tp-students                          Tạo thủ công
GET    /api/tp-students/:id                      Chi tiết
PATCH  /api/tp-students/:id                      Cập nhật
DELETE /api/tp-students/:id                      Soft delete
```

### Import

```
POST   /api/tp-imports                           Upload file
GET    /api/tp-imports/:id                       Trạng thái batch
PATCH  /api/tp-imports/:id/mapping               Lưu column mapping
GET    /api/tp-imports/:id/rows                  Danh sách rows + status
POST   /api/tp-imports/:id/apply-fixes           Áp dụng auto-fix
POST   /api/tp-imports/:id/execute               Thực hiện import
GET    /api/tp-imports/:id/error-report          Download file lỗi
```

---

## 7. UI Sitemap

```
/transport-programs                    Programs Hub
    /                                  Program List (data grid)
    /new                               Create Program (full page form)
    /:id/overview                      Program Overview (cards)
    /:id/schedule                      Schedule (calendar view)
    /:id/students                      Enrolled Students (grid)
    /:id/students/enroll               Enroll Students (full page search)
    /:id/attendance                    Attendance Hub (split: calendar + detail)
    /:id/attendance/:date              Day Attendance (checklist)
    /:id/driver-assignment             Driver Assignment (timeline/grid)
    /:id/cost                          Cost Tracking
    /:id/reports/absence               Absence Report (pivot)
    /:id/audit                         Audit Timeline

/students                              Student Master
    /                                  Student List (enterprise grid)
    /new                               Create Student
    /:id                               Student Detail
    /import                            Import Upload
    /import/:id/mapping                Column Mapping
    /import/:id/preview                Preview + Validation
    /import/:id/review                 Final Review
    /import/:id/result                 Result + Download
```

---

## 8. Laravel Module Structure

```
app/
├── Models/
│   ├── TpProgram.php
│   ├── TpProgramDay.php
│   ├── TpStudent.php
│   ├── TpEnrollment.php
│   ├── TpDayAbsence.php
│   ├── TpTripExecution.php
│   ├── TpTripStudentLog.php
│   ├── TpImportBatch.php
│   ├── TpImportRow.php
│   └── TpAuditLog.php
│
├── Http/Controllers/Api/
│   ├── TransportProgram/
│   │   ├── TpProgramController.php
│   │   ├── TpProgramLifecycleController.php
│   │   ├── TpProgramDayController.php
│   │   ├── TpProgramDayDriverController.php
│   │   ├── TpEnrollmentController.php
│   │   ├── TpDayAbsenceController.php
│   │   ├── TpAttendanceController.php
│   │   ├── TpExecutionCostController.php
│   │   ├── TpReportAbsenceController.php
│   │   ├── TpReportCostController.php
│   │   └── TpAuditController.php
│   │
│   ├── Driver/
│   │   ├── DriverTpDayListController.php
│   │   ├── DriverTpDayDetailController.php
│   │   ├── DriverTripStartController.php
│   │   ├── DriverTripCompleteController.php
│   │   ├── DriverStudentBoardController.php
│   │   ├── DriverStudentAlightController.php
│   │   ├── DriverStudentAbsentController.php
│   │   └── DriverTripSyncController.php
│   │
│   └── TpStudent/
│       ├── TpStudentController.php
│       ├── TpImportController.php
│       ├── TpImportMappingController.php
│       ├── TpImportExecuteController.php
│       └── TpImportErrorReportController.php
│
├── Services/TransportProgram/
│   ├── ProgramDayGeneratorService.php
│   ├── ProgramEnrollmentService.php
│   ├── AttendanceService.php
│   ├── DriverAssignmentService.php
│   ├── TripExecutionService.php
│   ├── StudentLogService.php
│   ├── OfflineSyncService.php
│   └── ProgramReportService.php
│
├── Services/TpImport/
│   ├── ImportParserService.php
│   ├── ImportValidatorService.php
│   ├── ImportAutoFixService.php
│   ├── ImportExecutorService.php
│   └── ImportErrorReportService.php
│
└── Actions/
    ├── CreateTransportProgramAction.php
    ├── UpdateProgramDateRangeAction.php
    ├── EnrollStudentsAction.php
    ├── StartTripExecutionAction.php
    └── SyncOfflineActionsAction.php

resources/js/src/
├── views/transportProgram/
│   ├── TpProgramListView.vue
│   ├── TpProgramCreateView.vue
│   ├── TpProgramWorkspaceView.vue       (shell + tabs)
│   │   ├── tabs/OverviewTab.vue
│   │   ├── tabs/ScheduleTab.vue
│   │   ├── tabs/StudentsTab.vue
│   │   ├── tabs/AttendanceTab.vue
│   │   ├── tabs/DriverAssignmentTab.vue
│   │   ├── tabs/CostTab.vue
│   │   ├── tabs/ReportsTab.vue
│   │   └── tabs/AuditTab.vue
│   ├── TpEnrollStudentsView.vue
│   └── TpDayAttendanceView.vue
│
├── views/tpStudent/
│   ├── TpStudentListView.vue
│   ├── TpStudentDetailView.vue
│   └── import/
│       ├── ImportUploadView.vue
│       ├── ImportMappingView.vue
│       ├── ImportPreviewView.vue
│       ├── ImportReviewView.vue
│       └── ImportResultView.vue
│
└── views/driver/
    ├── DriverTodayView.vue
    ├── DriverTripDetailView.vue
    ├── DriverAttendanceView.vue
    └── DriverTripSummaryView.vue
```

---

## 9. Migration Strategy

### Phase 0 — Parallel Schema (không downtime)

Chạy migrations tạo toàn bộ bảng `tp_*`. Hệ thống cũ (`policy_*`) vẫn chạy bình thường. Không đụng bảng cũ.

### Phase 1 — Data Migration

```sql
-- 1. Migrate students
INSERT INTO tp_students (id, code, full_name, grade, class_name, ...)
SELECT gen_random_uuid(), student_code, student_name, ...
FROM (
    SELECT DISTINCT student_code, student_name, ...
    FROM student_policies sp
    JOIN students s ON sp.student_id = s.id
) deduped
ON CONFLICT (code) DO NOTHING;

-- 2. Migrate programs (từ routes + time_slot combinations)
INSERT INTO tp_programs (name, departure_time, start_date, end_date, ...)
SELECT r.name || ' (' || sp.time_slot || ')', ...
FROM student_policies sp
JOIN routes r ON sp.route_id = r.id
GROUP BY r.id, sp.time_slot;

-- 3. Generate program_days từ existing trips
INSERT INTO tp_program_days (program_id, scheduled_date, expected_count)
SELECT tp.id, pt.trip_date, pt.expected_count
FROM policy_trips pt
JOIN tp_programs tp ON tp.source_trip_id = pt.id;  -- mapping bảng trung gian

-- 4. Migrate enrollments
INSERT INTO tp_enrollments (program_id, student_id, enrolled_at)
SELECT ...

-- 5. Migrate absences (từ policy_trip_students WHERE absence_reason IS NOT NULL)
INSERT INTO tp_day_absences (program_day_id, student_id, absence_type, ...)
SELECT ...
```

### Phase 2 — UI Cutover

- Deploy UI mới (`/transport-programs`) trỏ sang API `tp_*`
- Giữ UI cũ (`/policy-trips`) read-only cho dispatcher tra cứu lịch sử
- Feature flag per role để rollout dần

### Phase 3 — Legacy Freeze

Sau 30 ngày không có write traffic vào legacy endpoints:
- Khóa write: `POST /policy-trips`, `PATCH /student-policies`
- Giữ read: `GET /policy-trips` cho báo cáo lịch sử

### Phase 4 — Cleanup

Sau 30 ngày freeze thêm:
- Drop bảng cũ: `policy_trips`, `policy_trip_students`, `student_policies`, `policy_trip_audit`
- Xóa cron job `generate_policy_trips`
- Xóa controllers, services, routes cũ
