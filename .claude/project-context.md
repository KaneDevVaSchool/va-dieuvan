# Project Context — VA Điều Vận

## Mô tả dự án

**VA Điều Vận** là hệ thống quản lý điều vận xe cho trường học VA (Vietnam Academy). Hệ thống xử lý:
- Yêu cầu điều vận từ nhân viên/bộ phận
- Phân bổ xe và tài xế
- Quản lý chương trình vận chuyển học sinh định kỳ (Transport Program)
- Điểm danh học sinh, báo cáo, thanh toán

## Tech Stack

- **Backend**: Laravel 10, PHP 8.1+, MySQL
- **Frontend**: Vue 3 (Composition API), Pinia, Vue Router 4, Tailwind CSS 3
- **Auth**: Laravel Sanctum + Google OAuth (Socialite)
- **Permission**: Spatie Laravel Permission v6
- **Queue**: Laravel Queue (database driver)
- **Push**: Web Push (VAPID via minishlink/web-push)
- **PDF**: DomPDF
- **Excel**: OpenSpout (streaming), PhpSpreadsheet (complex)
- **Build**: Vite 5, TypeScript, PWA

## Cấu trúc thư mục quan trọng

```
app/
  Actions/        — Single-purpose actions (4 files)
  DTOs/           — Data Transfer Objects
  Http/
    Controllers/Api/  — Thin controllers (request → service → response)
    Middleware/       — Auth, role, feature, idempotency, throttle
    Requests/         — FormRequest validation
  Models/           — Eloquent models
  Services/         — Business logic (50+ services, organized by domain)
  Support/          — Utility classes (locks, visibility, access control)
  Notifications/    — Laravel notifications (email + push)
  Jobs/             — Queue jobs
  Listeners/        — Event listeners
  Policies/         — Authorization policies
  Repositories/     — Data access layer (hiện tại chỉ có 1)

resources/js/
  views/            — Vue page components (80+ views)
  components/       — Vue UI components (150+ components)
  composables/      — Vue composables (50+ hooks)
  stores/           — Pinia stores
  utils/            — Helper functions
  locales/          — i18n (vi/en)

routes/api/spa/   — Route files phân theo role
docs/             — Documentation
tests/Feature/    — PHPUnit feature tests (45+)
tests/Unit/       — PHPUnit unit tests (5+)
```

## Nguyên tắc kiến trúc

1. **Controller chỉ làm**: nhận request → validate → authorize → gọi service → trả response
2. **Business logic nằm trong Service layer** (`app/Services/`)
3. **Optimistic locking** cho concurrent operations (trips, attendance)
4. **Financial lock** bảo vệ dữ liệu đã thanh toán
5. **Idempotency** cho mutations quan trọng
6. **Audit log** mọi thao tác nghiệp vụ

## Roles & Permissions

```
superadmin    — Toàn quyền (bypass tất cả checks)
admin         — Quản trị hệ thống, users, roles
dispatcher    — Điều phối chuyến đi
department_head — Trưởng đơn vị (duyệt yêu cầu)
internal_user — Nhân viên nội bộ (gửi yêu cầu)
driver        — Tài xế (mobile-only access)
accountant    — Kế toán
```

## Domain Terminology

| Tiếng Việt | Tiếng Anh | Code |
|-----------|----------|------|
| Yêu cầu điều vận | Dispatch Request | `DispatchRequest` |
| Chuyến đi | Trip | `Trip` |
| Tài xế | Driver | `Driver` |
| Chương trình vận chuyển | Transport Program | `TpProgram` |
| Ngày thực hiện TP | Program Day | `TpProgramDay` |
| Học sinh TP | TP Student | `TpStudent` |
| Điểm danh | Attendance | `AttendanceService` |
| Điều phối viên | Dispatcher | User với role 'dispatcher' |
| Trưởng đơn vị | Department Head | User với role 'department_head' |
| Vắng mặt | Absence | `TpDayAbsence` |
| Thực thi chuyến | Trip Execution | `TpTripExecution` |

## Known Constraints

- Học sinh TP có mã code (`tp_students.code`) là định danh chính khi import
- Một chuyến trip phải thuộc về một DispatchRequest
- TP program day có thể có ca sáng + ca chiều (shift system)
- Financial lock: trip đã paid không thể sửa chi phí/hành khách
- Optimistic lock version check trước mọi concurrent mutation
