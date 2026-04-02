# Next steps – Phần mềm điều vận (Laravel + Vue)

Tài liệu này mô tả **các bước triển khai tiếp theo** dựa trên scaffold hiện tại (Laravel 10 + Sanctum + Vue 3 + Pinia + Vue Router) và BRD “Phần mềm điều vận”.

---

## 1) Yêu cầu môi trường

- **PHP**: 8.1+
- **Composer**
- **Node.js + npm**
- **MySQL 8+**

---

## 2) Cấu hình môi trường `.env`

### Backend

- **APP_KEY**: chạy `php artisan key:generate` (nếu chưa có)
- **DB**: hệ thống đang dùng MySQL, hãy đảm bảo MySQL chạy và chỉnh:
  - `DB_HOST`, `DB_PORT`
  - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### Authentication

Hiện scaffold dùng **Laravel Sanctum** (`auth:sanctum` trong `routes/api.php`).

---

## 3) Khởi tạo database schema & RBAC

Chạy migrate:

```bash
php artisan migrate
```

Chạy seed RBAC (roles/permissions):

```bash
php artisan db:seed
```

Seeder RBAC nằm tại:
- `database/seeders/RbacSeeder.php`

Các role mặc định:
- `admin`, `dispatcher`, `driver`, `accountant`, `internal_user`

---

## 4) Frontend: có bắt buộc chạy `npm run dev` không?

**Không bắt buộc**.

### Cách A – Dev mode (hot reload)

```bash
npm run dev
php artisan serve
```

### Cách B – Không chạy dev server (khuyến nghị cho staging/prod)

Build một lần:

```bash
npm run build
php artisan serve
```

Khi đó Laravel sẽ serve assets đã build từ `public/build/`.

### Cách C – Không chạy npm (chỉ backend)

Nếu bạn **không muốn chạy npm** ở môi trường nào đó, cần đảm bảo view không phụ thuộc `@vite(...)`.
Hiện `resources/views/welcome.blade.php` đã mount Vue vào `#app` và có `@vite(...)`, nên nếu không build assets thì UI sẽ không load.

---

## 5) Cấu trúc module backend (định hướng production)

### Controllers (API)

Định hướng đặt theo module:

- `app/Http/Controllers/Api/RequestController.php`
- (tạo tiếp) `TripController`, `VehicleController`, `DriverController`, `ReportController`, `CostController`

### Services (business logic)

Đặt theo domain:

- `app/Services/Dispatching/DispatchingService.php`
  - gán xe/tài xế/NCC
  - check trùng lịch
  - optimistic locking
- `app/Services/Costs/CostCalculationService.php`
  - tổng hợp chi phí, đối soát

### Middleware

- `app/Http/Middleware/EnsureHasRole.php`
  - alias `role` trong `app/Http/Kernel.php`

Khuyến nghị bước tiếp theo:
- tạo `EnsureHasPermission` (kiểm tra theo permission thay vì role)

---

## 6) Database entities đã scaffold (BRD core)

### RBAC

- `roles`, `permissions`
- `role_user`, `permission_role`

### Core vận hành

- `vehicles`
- `drivers`
- `transport_providers` (NCC/Taxi)
- `dispatch_requests` (yêu cầu điều xe)
- `trips` (vòng đời chuyến)
- `trip_records` (km start/end, notes…)
- `trip_costs` (xăng, phí cầu đường…)

### Audit & notifications

- `audit_logs`
- `notifications`

---

## 7) Business rules (ưu tiên implement tiếp theo)

### BR-001 – Yêu cầu tạo trước giờ xuất phát ít nhất 2 tiếng

Gợi ý triển khai:
- Validate ở API khi tạo/sửa `dispatch_requests`:
  - `depart_at >= now() + 2 hours`
- Nếu “ngoài 2 tiếng” thì chuyển sang trạng thái cần duyệt thủ công (tuỳ workflow)

### BR-002 – Chống trùng lịch xe/tài xế (Optimistic locking)

Hiện `trips` có:
- `lock_version` (default 0)

Gợi ý triển khai:
- Khi dispatcher “assign” tài nguyên:
  - client gửi `lock_version` hiện tại
  - server update với điều kiện `WHERE id = ? AND lock_version = ?`
  - tăng `lock_version = lock_version + 1`
- Đồng thời check trùng lịch theo time window:
  - `(trip.depart_at, trip.arrive_by)` overlap với trips khác của cùng `vehicle_id` / `driver_id`

### Audit log – ghi nhận toàn bộ thao tác phê duyệt/override

Gợi ý triển khai:
- Observer (Eloquent) hoặc service layer:
  - log event: `request.approve`, `trip.assign`, `cost.confirm`, `data.override_confirmed`
  - lưu `before/after` (json)

---

## 8) API modules cần triển khai (theo BRD)

### Requests
- Tạo/sửa/huỷ yêu cầu
- Duyệt/từ chối

### Trips / Dispatching
- Phân công xe/tài xế/NCC
- Driver confirm / start / complete / incident

### Resources
- CRUD `vehicles`, `drivers`, `transport_providers`

### Costs & Reconcile
- Driver submit cost
- Accountant confirm/reject cost
- Reconcile theo kỳ

### Reports
- Trip report, cost report, driver performance
- Export Excel/PDF

---

## 9) Checklist nhanh trước khi bàn giao dev/staging

- `.env` tách môi trường Dev/Staging/Prod
- migrate/seed chạy ổn
- RBAC enforced ở routes + policy/middleware
- audit log hoạt động cho approve/assign/confirm/override
- conflict check trùng lịch có test cases

