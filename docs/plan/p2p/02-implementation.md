# 02 — Checklist Triển khai

> Danh sách đầy đủ những gì cần build. Đánh dấu ✅ khi hoàn thành từng item.

---

## PHASE 0 — Database & Schema

### Migrations (thứ tự phụ thuộc)

```
[ ] 2026_xx_xx_001_create_tp_programs_table.php
[ ] 2026_xx_xx_002_create_tp_program_days_table.php
[ ] 2026_xx_xx_003_create_tp_students_table.php
[ ] 2026_xx_xx_004_create_tp_enrollments_table.php
[ ] 2026_xx_xx_005_create_tp_day_absences_table.php
[ ] 2026_xx_xx_006_create_tp_trip_executions_table.php
[ ] 2026_xx_xx_007_create_tp_trip_student_logs_table.php
[ ] 2026_xx_xx_008_create_tp_import_batches_table.php
[ ] 2026_xx_xx_009_create_tp_import_rows_table.php
[ ] 2026_xx_xx_010_create_tp_audit_logs_table.php
```

### Models (Eloquent)

```
[ ] TpProgram.php
      relationships: hasMany(TpProgramDay), hasMany(TpEnrollment)
      casts: runs_on (array), excluded_dates (array), extra_dates (array)
      scopes: active(), draft(), withEffectiveDriver()

[ ] TpProgramDay.php
      relationships: belongsTo(TpProgram), hasOne(TpTripExecution), hasMany(TpDayAbsence)
      computed: effectiveDriver(), effectiveVehicle(), effectiveCost()

[ ] TpStudent.php
      relationships: hasMany(TpEnrollment), hasMany(TpTripStudentLog)
      scopes: active(), byClass(), search()

[ ] TpEnrollment.php
      relationships: belongsTo(TpProgram), belongsTo(TpStudent)
      scopes: active()

[ ] TpDayAbsence.php
      relationships: belongsTo(TpProgramDay), belongsTo(TpStudent)

[ ] TpTripExecution.php
      relationships: belongsTo(TpProgramDay), hasMany(TpTripStudentLog)
      casts: driver_snapshot (array), vehicle_snapshot (array)

[ ] TpTripStudentLog.php
      relationships: belongsTo(TpTripExecution), belongsTo(TpStudent)
      casts: student_snapshot (array)

[ ] TpImportBatch.php
      relationships: hasMany(TpImportRow)
      casts: column_mapping (array), auto_fix_rules (array)

[ ] TpImportRow.php
      relationships: belongsTo(TpImportBatch), belongsTo(TpStudent)
      casts: raw_data, mapped_data, fixed_data, validation_errors (all array)

[ ] TpAuditLog.php
      relationships: belongsTo(TpProgram)
```

---

## PHASE 1 — Backend Core (Program + Day + Enrollment)

### Services

```
[ ] ProgramDayGeneratorService
      generate(TpProgram): int
        - expandDateRange(start, end, runs_on, excluded, extra) → Collection<Date>
        - UPSERT tp_program_days (ON CONFLICT program_id+scheduled_date)
        - Return count generated

      regenerate(TpProgram, oldDateSet, newDateSet): void
        - days_to_add: newSet - oldSet → INSERT
        - days_to_remove: oldSet - newSet:
            has_data? → SET day_type = 'cancelled'
            no_data?  → DELETE

[ ] ProgramEnrollmentService
      enrollBulk(TpProgram, studentIds[]): EnrollmentResult
        - Validate: no active duplicate
        - Batch INSERT tp_enrollments
        - UPDATE expected_count on future days

      unenroll(TpProgram, studentId, reason): void
        - SET unenrolled_at, unenrolled_by, unenroll_reason
        - UPDATE expected_count on future un-executed days

[ ] AttendanceService
      getAttendance(TpProgramDay): AttendanceList
        - Fetch enrolled students
        - Fetch absences
        - Merge: status = absent | attending

      markAbsent(TpProgramDay, studentId, type, reason): void
        - INSERT tp_day_absences (ON CONFLICT DO UPDATE)
        - Decrement expected_count

      unmarkAbsent(TpProgramDay, studentId): void
        - DELETE tp_day_absences
        - Increment expected_count

[ ] DriverAssignmentService
      assignDriver(TpProgramDay, driverId, vehicleId): void
        - checkConflict(driverId, date, time) → 409 if conflict
        - UPDATE tp_program_days SET driver_id, vehicle_id
        - Push notification to driver

      resolveEffective(TpProgramDay): {driver, vehicle, cost}
        - day.driver_id ?? program.default_driver_id
        - day.vehicle_id ?? program.default_vehicle_id
        - day.estimated_cost ?? program.cost_per_trip
```

### Actions

```
[ ] CreateTransportProgramAction
      execute(data): TpProgram
        - DB::transaction
        - INSERT tp_programs
        - ProgramDayGeneratorService::generate()
        - Audit: program.created

[ ] UpdateProgramDateRangeAction
      execute(program, newData): void
        - Detect date range change
        - ProgramDayGeneratorService::regenerate()
        - Audit: program.date_range_updated

[ ] EnrollStudentsAction
      execute(program, studentIds[]): EnrollmentResult
        - ProgramEnrollmentService::enrollBulk()
        - Audit: enrollment.bulk_enrolled

[ ] StartTripExecutionAction
      execute(TpProgramDay, driver): TpTripExecution
        - Resolve effective driver/vehicle/cost
        - CREATE tp_trip_executions (snapshot)
        - Seed tp_trip_student_logs (merge pre-absences)
        - Audit: execution.started
```

### Controllers

```
[ ] TpProgramController          (index, store, show, update, destroy)
[ ] TpProgramLifecycleController (activate, pause, cancel)
[ ] TpProgramDayController       (index, show, update)
[ ] TpProgramDayDriverController (assign, remove)
[ ] TpEnrollmentController       (index, store, destroy)
[ ] TpDayAbsenceController       (store, destroy) — cũng handle bulk
[ ] TpAttendanceController       (show) — GET attendance for a day
[ ] TpAuditController            (index)
[ ] TpReportAbsenceController    (index) — pivot table
```

### Form Requests

```
[ ] StoreTpProgramRequest
[ ] UpdateTpProgramRequest
[ ] ListTpProgramsRequest
[ ] AssignDayDriverRequest
[ ] BulkEnrollRequest
[ ] MarkAbsentRequest
[ ] ListTpProgramDaysRequest
[ ] TpReportAbsenceRequest
```

---

## PHASE 2 — Backend Driver Layer

### Services

```
[ ] TripExecutionService
      start(TpProgramDay, driver): TpTripExecution
        → Delegates to StartTripExecutionAction

      complete(TpTripExecution, confirmPendingBoard): void
        - Validate: no 'pending' students
        - Warn path: có 'boarded' chưa 'alighted' → require confirm
        - UPDATE status = 'completed', completed_at
        - Alert dispatcher về boarded-not-alighted
        - Audit: execution.completed

      cancel(TpTripExecution, reason): void

[ ] StudentLogService
      board(TpTripStudentLog, clientTs?): void
      alight(TpTripStudentLog, clientTs?): void
      markAbsent(TpTripStudentLog, type, notes, clientTs?): void
        → Also INSERT/UPDATE tp_day_absences (2-way sync)
      
[ ] OfflineSyncService
      processQueue(TpTripExecution, actions[]): SyncResult
        - Sort by client_timestamp
        - Apply board/alight/absent in order
        - Detect conflicts with server state
        - Return conflicts[]
        - Notify dispatcher if conflicts > 0

[ ] TripExecutionCostService
      updateActualCost(TpTripExecution, cost, notes): void
```

### Controllers (Driver)

```
[ ] DriverTpDayListController    GET /driver/tp-days
[ ] DriverTpDayDetailController  GET /driver/tp-days/:id
[ ] DriverTripStartController    POST /driver/tp-days/:id/start
[ ] DriverTripCompleteController POST /driver/tp-executions/:id/complete
[ ] DriverStudentBoardController PATCH .../board
[ ] DriverStudentAlightController PATCH .../alight
[ ] DriverStudentAbsentController PATCH .../absent
[ ] DriverTripSyncController     POST .../sync
[ ] TpExecutionCostController    PATCH /tp-executions/:id/cost
```

---

## PHASE 3 — Backend Import

### Services

```
[ ] ImportParserService
      parse(filePath, fileType): ParseResult
        - xlsx/xls: PhpSpreadsheet
        - csv: League/CSV
        - Extract headers, rows
        - Detect encoding

[ ] ImportValidatorService
      validate(batch, columnMapping): ValidationResult
        - Apply column mapping to raw_data
        - Per-row validation:
            full_name: required, 2-100 chars
            code: unique check (warn duplicate, error exact active match)
            parent_phone: regex Vietnamese, normalize
            grade/class_name: format check
            campus: FK existence check
        - UPDATE tp_import_rows validation_status + validation_errors

[ ] ImportAutoFixService
      applyFixes(batch, rules): FixResult
        - normalize_phone: 0xx → +84xx
        - trim_whitespace: tên, mã
        - capitalize_name: Nguyen van A → Nguyễn Văn A
        - uppercase_code: hs001 → HS001
        - Re-run validation sau fix

[ ] ImportExecutorService
      execute(batch, options): ExecutionResult
        - options: {skipErrors, includeWarnings, targetProgramId?}
        - Partial import: process valid/warning rows, skip errors
        - INSERT tp_students (ON CONFLICT code DO UPDATE nếu update mode)
        - UPDATE tp_import_rows: import_status, student_id
        - UPDATE tp_import_batches: imported_rows, status = 'completed'
        - Auto-enroll nếu target_program_id provided

[ ] ImportErrorReportService
      generate(batch): string (file path)
        - Excel file 3 sheets: Lỗi / Cảnh báo / Thành công
        - Return path để download
```

### Controllers

```
[ ] TpImportController           POST /tp-imports (upload)
[ ] TpImportStatusController     GET /tp-imports/:id
[ ] TpImportMappingController    PATCH /tp-imports/:id/mapping
[ ] TpImportRowController        GET /tp-imports/:id/rows
[ ] TpImportFixController        POST /tp-imports/:id/apply-fixes
[ ] TpImportExecuteController    POST /tp-imports/:id/execute
[ ] TpImportErrorReportController GET /tp-imports/:id/error-report
```

---

## PHASE 4 — Backend Student CRUD

```
[ ] TpStudentController (index, store, show, update, destroy)
      search: full_name, code, grade, class_name, campus_id
      filters: status, grade, class_name
      
[ ] TpStudentProgramHistoryController GET /tp-students/:id/programs
```

---

## PHASE 5 — Frontend: Programs

```
[ ] TpProgramListView.vue
      - Enterprise data grid
      - Filter: status, responsible_user, date range
      - Bulk actions: activate, pause, cancel
      - Empty state với CTA "Tạo chương trình đầu tiên"

[ ] TpProgramCreateView.vue
      - Full page form, 2 cột
      - Section 1: Thông tin cơ bản (name, description)
      - Section 2: Hành trình (origin → destination, times)
      - Section 3: Lịch vận hành (date range, runs_on, excludes)
      - Section 4: Tài xế mặc định (default_driver, default_vehicle)
      - Section 5: Chi phí (cost_per_trip)
      - Preview: "Hệ thống sẽ sinh X ngày vận hành"
      - Sticky action bar

[ ] TpProgramWorkspaceView.vue
      - Shell layout với sticky tab bar
      - Breadcrumb: Programs > [name]
      - Status badge + quick actions (Activate / Pause / Cancel)

[ ] tabs/OverviewTab.vue
      - Cards: tổng ngày, tổng học sinh, absent rate
      - Progress timeline (last 7 days)

[ ] tabs/ScheduleTab.vue
      - Calendar view theo tháng
      - Màu: xanh = operating, đỏ = cancelled, vàng = makeup
      - Badge: số vắng mỗi ngày
      - Click ngày → navigate sang Day Attendance

[ ] tabs/StudentsTab.vue
      - Grid học sinh enrolled
      - Inline unenroll với reason
      - Link sang Enroll Students

[ ] TpEnrollStudentsView.vue
      - Full page search học sinh
      - Checkbox grid (search real-time)
      - Filter by grade/class
      - Preview panel: học sinh sắp enroll
      - Sticky "Đăng ký X học sinh" button

[ ] tabs/AttendanceTab.vue
      - Split view: calendar trái, detail phải
      - Click ngày → load TpDayAttendancePanel

[ ] TpDayAttendanceView.vue (hoặc panel trong Attendance tab)
      - Checklist grid học sinh
      - Toggle: Có mặt / Vắng
      - Khi vắng: dropdown chọn loại vắng
      - Bulk: đánh dấu nhiều em cùng lúc
      - Header: headcount summary
      - Sticky save

[ ] tabs/DriverAssignmentTab.vue
      - Grid các ngày trong tháng
      - Hiện driver của mỗi ngày (override hoặc default với badge)
      - Inline assign / clear override
      - Filter: tháng, chưa gán

[ ] tabs/CostTab.vue
      - Grid execution theo tháng
      - Cột: ngày, driver, ước tính, thực tế, status
      - Inline edit actual_cost
      - Summary: tổng tháng

[ ] tabs/ReportsTab.vue
      - Pivot table vắng (học sinh × ngày)
      - Legend 4 loại vắng
      - Filter: tuần, tháng
      - Export Excel

[ ] tabs/AuditTab.vue
      - Timeline audit log
      - Filter: entity_type, action
      - Expandable để xem before/after JSON
```

---

## PHASE 6 — Frontend: Students

```
[ ] TpStudentListView.vue
      - Enterprise data grid
      - Search: full_name, code
      - Filter: status, grade, class_name, campus
      - Bulk: activate, deactivate, export
      - Import button → /students/import

[ ] TpStudentDetailView.vue
      - Tabs: Thông tin | Chương trình tham gia | Lịch sử
      - Chương trình tham gia: list programs + enrollment dates

[ ] import/ImportUploadView.vue
      - Drag & drop zone
      - Accept: xlsx, xls, csv
      - Size limit: 10MB
      - Preview file info sau upload

[ ] import/ImportMappingView.vue
      - Side-by-side: detected headers ↔ DB fields
      - Drag hoặc dropdown để map
      - Auto-suggest (fuzzy match)
      - Preview 5 dòng đầu với mapping áp dụng

[ ] import/ImportPreviewView.vue
      - Data grid phân loại: Valid (xanh) / Warning (vàng) / Error (đỏ)
      - Tab filter: Tất cả / Valid / Warning / Error
      - Inline error messages per row (expandable)
      - Auto-fix panel:
          ☑ Chuẩn hóa số điện thoại
          ☑ Xóa khoảng trắng
          ☑ Capitalize tên
          ☑ Uppercase mã học sinh
      - [Áp dụng Auto-fix] button

[ ] import/ImportReviewView.vue
      - Summary cards: X valid, X warning, X error
      - Options: include warnings, skip errors
      - [Import X học sinh] CTA button

[ ] import/ImportResultView.vue
      - Progress bar khi importing
      - Final summary cards
      - Download lỗi button (nếu có)
      - Link sang danh sách học sinh vừa import
```

---

## PHASE 7 — Frontend: Driver Mobile App

```
[ ] DriverTodayView.vue
      - List chuyến hôm nay của driver
      - Card mỗi chuyến: tên chương trình, giờ, headcount, status
      - Badge "Mặc định" nếu dùng program default driver

[ ] DriverTripDetailView.vue
      - Thông tin trước khi bắt đầu: xe, giờ, số HS
      - [Bắt đầu chuyến] CTA
      - Offline indicator

[ ] DriverAttendanceView.vue
      - List học sinh với 3 nút action: [Lên xe] [Xuống xe] [Vắng]
      - Pre-absent rows: readonly, badge "Đã báo trước"
      - Pending rows: nổi bật cần xử lý
      - Progress: "X/Y học sinh đã xử lý"
      - Offline queue indicator (sync pending count)

[ ] components/AbsenceReasonModal.vue
      - Bottom sheet (mobile-first)
      - Radio: Vắng không báo / Hủy muộn
      - Text note (optional)
      - Confirm button

[ ] DriverTripSummaryView.vue
      - Sau khi complete
      - Summary: boarded, alighted, absent
      - [Hoàn thành] hoặc hiện conflicts nếu sync có vấn đề
```

---

## PHASE 8 — Realtime & Notifications

```
[ ] SSE endpoint: GET /api/tp-programs/:id/days/:date/live
      - Events: attendance_changed, execution_started, execution_completed
      - Broadcast khi: board/alight/absent xảy ra

[ ] Push notification (driver):
      - Khi được assign vào ngày mới
      - Khi program thay đổi default driver

[ ] In-app notification (dispatcher):
      - Vắng không phép từ driver
      - Conflict sau offline sync
      - Chuyến hoàn thành có HS boarded chưa alighted
      - Chuyến chưa có driver 2h trước giờ xe
```

---

## PHASE 9 — Data Migration (khi ready)

```
[ ] Viết migration script: student_policies → tp_students
[ ] Viết migration script: routes + time_slots → tp_programs
[ ] Viết migration script: policy_trips → tp_program_days
[ ] Viết migration script: student_policies → tp_enrollments
[ ] Viết migration script: policy_trip_students (absent) → tp_day_absences
[ ] Viết migration script: policy_trips + policy_trip_students → tp_trip_executions + tp_trip_student_logs
[ ] Dry-run migration trên staging + verify counts
[ ] UI cutover: redirect /policy-trips → /transport-programs
[ ] Legacy write-lock sau 30 ngày
[ ] Cleanup: drop policy_* tables, remove cron job
```

---

## PHASE 10 — Tests & QA

```
[ ] Unit: ProgramDayGeneratorService
      - Expand date range đúng với runs_on
      - Exclude dates đúng
      - Extra dates đúng
      - Regen: thêm/bỏ ngày đúng

[ ] Unit: AttendanceService
      - Default attending (không có absence record)
      - Mark absent → GET trả về absent
      - Unmark → trở về attending
      - expected_count chính xác

[ ] Unit: DriverAssignmentService
      - Conflict check đúng (cùng ngày, giờ overlap)
      - Resolve effective: override → default

[ ] Unit: StartTripExecutionAction
      - Seed logs từ enrollments
      - Pre-absent mapped đúng từ day_absences

[ ] Unit: OfflineSyncService
      - Sort by client_timestamp
      - Apply actions đúng thứ tự
      - Detect conflict đúng

[ ] Integration: Import pipeline
      - xlsx parse đúng
      - Validation: valid / warning / error đúng
      - Auto-fix normalize phone đúng
      - Partial import: skip errors, import valid
      - Error report có đủ 3 sheets

[ ] E2E: Core flow
      - Tạo chương trình → sinh 270 ngày
      - Enroll 50 học sinh → expected_count đúng
      - Đánh vắng ngày X → chỉ ngày X bị ảnh hưởng
      - Driver start → seed logs đúng
      - Driver board/alight/absent → counters đúng
      - Driver complete → status completed

[ ] E2E: Offline sync
      - Simulate mất mạng → board/alight → lấy mạng → sync
      - Verify timestamps từ client được dùng
      - Verify conflict detection khi dispatcher action trùng

[ ] Load: Generate 365 ngày cho 1 program → < 500ms
[ ] Load: Enroll 500 học sinh → expected_count update < 1s
[ ] Load: Seed 500 student logs khi start trip → < 1s
```
