# Báo cáo Hiệu năng — VA Điều Vận

**Ngày audit**: 2026-06-10

---

## Tóm tắt

| Mức độ | Số vấn đề |
|--------|----------|
| CRITICAL | 0 |
| HIGH | 3 |
| MEDIUM | 5 |
| LOW | 4 |

---

## HIGH

### PERF-H1: N+1 Query tiềm ẩn trong AttendanceService
**File**: `app/Services/TransportProgram/AttendanceService.php:532-545`  
**Vấn đề**: `boardedAtMapForDay()` gọi `$execution->studentLogs()->get()`. Nếu `getAttendance()` được gọi nhiều lần hoặc trong loop, sẽ query N lần.  
**Giải pháp**: Eager load `studentLogs` khi load `TpProgramDay` ở tầng controller.  
**Estimated impact**: 10-50ms mỗi request danh sách ngày

### PERF-H2: Trip stats tạo 3 separate queries
**File**: `app/Http/Controllers/Api/Trips/TripController.php:129-183`  
**Vấn đề**: `stats()` clone base query 3 lần → 3 SQL queries. Với filter phức tạp (nhiều JOIN), mỗi query có thể 50-200ms.  
**Giải pháp**: Dùng conditional aggregation:
```sql
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
  SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
FROM trips
WHERE ...
```
**Estimated savings**: ~150ms per stats request

### PERF-H3: Excel report không stream
**File**: `app/Services/Reports/TripCostReportXlsxWriter.php`, `DriverFrequencyReportXlsxWriter.php`  
**Vấn đề**: Load toàn bộ report data vào memory. Với 1000+ trips, có thể OOM.  
**Giải pháp**: Dùng OpenSpout streaming writer (đã có package) thay vì PhpSpreadsheet.  
**Estimated impact**: Memory từ 200MB+ xuống <50MB

---

## MEDIUM

### PERF-M1: Thiếu caching cho FeatureToggle
**File**: `app/Repositories/FeatureToggleRepository.php`  
**Vấn đề**: `FeatureToggle` được query từ DB mỗi request thay vì cache.  
**Giải pháp**: Cache với `Cache::remember('feature_toggles', 300, fn() => ...)`.

### PERF-M2: Thiếu Route Cache, Config Cache trong dev
**Vấn đề**: Không có guidance trong README về việc chạy artisan cache commands.

### PERF-M3: navBadges polling có thể tối ưu
**File**: `resources/js/navBadges.js`  
**Vấn đề**: Nếu polling interval ngắn, tạo nhiều requests không cần thiết.  
**Giải pháp**: Dùng WebSocket/SSE thay vì polling cho realtime badges.

### PERF-M4: Trip list query với nhiều orWhereHas
**File**: `app/Http/Controllers/Api/Trips/TripController.php:77-89`  
**Vấn đề**: Search query dùng `orWhereHas` nhiều lần — mỗi `whereHas` sinh một subquery.  
**Giải pháp**: Dùng LEFT JOIN thay vì subqueries cho search.

### PERF-M5: Không có queue cho send notifications
**Vấn đề**: Một số notification được gửi synchronously trong request lifecycle.  
**Giải pháp**: Implement `ShouldQueue` trên tất cả Notification classes.

---

## LOW

### PERF-L1: Asset build không có code splitting
**File**: `vite.config.js`  
**Vấn đề**: Tất cả routes cùng bundle. Lazy load sẽ cải thiện initial load time.  
**Giải pháp**: Vue Router lazy loading `() => import('./views/...')`.

### PERF-L2: Images không optimize trước upload
**File**: `resources/js/utils/imageCompress.js` — đã có, cần kiểm tra usage

### PERF-L3: Không có Redis cache driver
**Vấn đề**: `CACHE_DRIVER=file` hoặc `database` chậm hơn Redis đáng kể.  
**Giải pháp**: Configure Redis khi scale.

### PERF-L4: Query cho menu/nav items mỗi request
**File**: `app/Services/System/MenuService.php`  
**Giải pháp**: Cache menu per role.

---

## Benchmarks mục tiêu

| Endpoint | Target P95 |
|----------|-----------|
| GET /api/trips | < 200ms |
| GET /api/trips/stats | < 300ms |
| GET /api/tp-attendance/{id} | < 150ms |
| POST /api/trips/{id}/assign | < 100ms |
| GET /api/driver/tp-days | < 150ms |
