# Technical Debt Report

**Ngày audit**: 2026-06-10  
**Phiên bản**: Laravel 10 + Vue 3

---

## Tóm tắt

| Mức độ | Số vấn đề |
|--------|----------|
| CRITICAL | 2 |
| HIGH | 6 |
| MEDIUM | 9 |
| LOW | 8 |

---

## CRITICAL

### TD-C1: Missing DB::transaction trong updatePassengerList (cargo/business path)
**File**: `app/Http/Controllers/Api/Trips/TripController.php:341-409`  
**Vấn đề**: Phần xử lý `cargo`, `business`, `point_to_point` rows trong `updatePassengerList()` modify `$dr->wizard_snapshot`, `$dr->passenger_count` và gọi `$dr->save()` nhưng không wrap trong `DB::transaction()`. Nếu AuditLogger fail sau `save()`, dữ liệu không đồng nhất.  
**Giải pháp**: Wrap toàn bộ block trong `DB::transaction()`.  
**Ưu tiên**: P0 — fix ngay

### TD-C2: markAbsentBulk không atomic
**File**: `app/Services/TransportProgram/AttendanceService.php:207-220`  
**Vấn đề**: `markAbsentBulk()` gọi `markAbsent()` trong loop, mỗi học sinh một transaction riêng. Nếu thất bại ở giữa, một số học sinh bị đánh vắng, số còn lại không.  
**Giải pháp**: Wrap toàn bộ loop trong một transaction cha; hoặc dùng bulk INSERT với `updateOrInsert`.  
**Ưu tiên**: P0 — fix ngay

---

## HIGH

### TD-H1: Business logic trong TripController
**File**: `app/Http/Controllers/Api/Trips/TripController.php:415-600`  
**Vấn đề**: Các method `normalizePassengerRows()`, `normalizeBusinessRows()`, `normalizeCargoRows()`, `passengerRowFilled()`, `businessRowFilled()`, `cargoRowFilled()`, `sumGuestsPassengerAndBusiness()`, `sumCargoQty()` là business logic nên nằm trong Service, không phải Controller.  
**Giải pháp**: Tạo `App\Services\Trips\PassengerListNormalizerService` hoặc `App\DTOs\Trips\PassengerListDTO`.  
**Ưu tiên**: P1

### TD-H2: Trùng lặp $trip->load() array
**File**: `app/Http/Controllers/Api/Trips/TripController.php:307-319, 391-403, 622-634, 661-673`  
**Vấn đề**: Cùng một mảng eager-loading xuất hiện 4+ lần trong TripController. DRY violation.  
**Giải pháp**: Extract ra private method `loadTripRelations(Trip $trip)`.  
**Ưu tiên**: P1

### TD-H3: Dynamic column access trong AttendanceService
**File**: `app/Services/TransportProgram/AttendanceService.php:428-436`  
**Vấn đề**: `$day->{$statusCol}` sử dụng dynamic property tên cột (`morning_attendance_status`, `afternoon_attendance_status`). Không type-safe, IDE không phân tích được, dễ lỗi khi refactor.  
**Giải pháp**: Dùng `$day->getAttributeValue($col)` hoặc DTO riêng cho shift state.  
**Ưu tiên**: P1

### TD-H4: Thiếu Repository pattern — chỉ có 1 Repository
**File**: `app/Repositories/FeatureToggleRepository.php`  
**Vấn đề**: Chỉ có 1 Repository trong khi code query phức tạp nằm rải rác trong Services (đặc biệt TripCostReportService, DriverFrequencyReportService, AttendanceService).  
**Giải pháp**: Tạo thêm `TripRepository`, `TpAttendanceRepository` cho các query phức tạp.  
**Ưu tiên**: P2

### TD-H5: Thiếu Resource/Transformer cho trip response
**File**: `app/Http/Controllers/Api/Trips/TripController.php:210-212`  
**Vấn đề**: Controller trả thẳng Eloquent model với `$trip->setAttribute()` không qua API Resource. Cấu trúc response không rõ ràng, dễ rò rỉ field nhạy cảm.  
**Giải pháp**: Tạo `TripResource` extends `JsonResource`.  
**Ưu tiên**: P1

### TD-H6: Thiếu index trên trips.driver_id + depart_at
**File**: Database  
**Vấn đề**: Query driver workload và TP trip lookup không có composite index.  
**Giải pháp**: Thêm migration tạo index như đề xuất trong `docs/database/indexes.md`.  
**Ưu tiên**: P1

---

## MEDIUM

### TD-M1: Thiếu Policy cho Trip
**Vấn đề**: Trip authorization dùng `abort_unless(TripVisibility::userCanViewTrip(...))` thay vì Laravel Policy. Không tận dụng được `authorize()` trong FormRequest.  
**Giải pháp**: Tạo `TripPolicy` với `view`, `update`, `assign` methods.

### TD-M2: User model có hardcoded role names
**File**: `app/Models/User.php:64`  
**Vấn đề**: `hasAnyRole(['admin', 'dispatcher', 'department_head', 'internal_user'])` — role names hardcoded. Nếu đổi tên role sẽ break.  
**Giải pháp**: Constants hoặc config.

### TD-M3: Trip stats query không hiệu quả
**File**: `app/Http/Controllers/Api/Trips/TripController.php:129-183`  
**Vấn đề**: `(clone $base)->count()` + `(clone $base)->select(...)->groupBy(...)` tạo nhiều queries riêng. Có thể dùng conditional aggregation trong 1 query.  
**Giải pháp**: `CASE WHEN status='completed' THEN 1 END` pattern.

### TD-M4: Không có Form Requests cho một số endpoints
**Vấn đề**: Một số controllers validate inline thay vì dùng FormRequest class.

### TD-M5: Tests không cover TP attendance confirmation flow
**Vấn đề**: `TpAttendanceSubsystemTest.php` thiếu test case cho concurrent confirm với optimistic locking.

### TD-M6: Không có DTOs cho Attachment/SignedDocument pipeline
**Vấn đề**: Data được truyền dưới dạng array từ controller đến Service.

### TD-M7: Service constructor injection nhất quán nhưng một số chỗ dùng app()
**Vấn đề**: Trong TripController::show() dùng `app(\App\Services\...)` thay vì inject vào constructor.

### TD-M8: Thiếu logging cho failed push notifications
**File**: `app/Services/Notifications/WebPushSender.php`  
**Vấn đề**: Nếu push notification fail, có thể không log error đầy đủ.

### TD-M9: Excel exports không stream với large datasets
**Vấn đề**: `TripCostReportXlsxWriter` và `DriverFrequencyReportXlsxWriter` load toàn bộ data vào memory trước khi write.

---

## LOW

### TD-L1: Thiếu .env.example đầy đủ cho VAPID keys
### TD-L2: `app()` facade helper dùng trong một số Services
### TD-L3: Không có API versioning (all routes at /api/*)
### TD-L4: Blade email templates không có text/plain version
### TD-L5: README.md trống (chỉ có scaffold mặc định của Laravel)
### TD-L6: Thiếu PHPStan/Larastan config
### TD-L7: Một số console commands không có `--force` flag cho production
### TD-L8: Frontend không có error boundary component

---

## Lịch trình fix đề xuất

| Sprint | Items |
|--------|-------|
| Sprint 1 (ngay) | TD-C1, TD-C2 |
| Sprint 2 | TD-H1, TD-H2, TD-H5 |
| Sprint 3 | TD-H3, TD-H4, TD-H6 |
| Sprint 4+ | TD-M* và TD-L* |
