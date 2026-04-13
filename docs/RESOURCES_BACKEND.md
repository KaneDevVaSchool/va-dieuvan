# Thiết kế backend — Quản lý nguồn lực (xe, tài xế, đối tác)

Tài liệu mô tả mô hình dữ liệu, quyền, API và luồng **gán tài xế từ người dùng** (`users`), phù hợp với schema hiện có (`vehicles`, `drivers`, `transport_providers`, `users`).

## 1. Nguyên tắc nghiệp vụ

1. **Tài xế không tạo “hồ sơ rời”**: bản ghi `drivers` luôn gắn với **một user** (`drivers.user_id`) khi được “gán tài xế” từ hệ thống. `full_name` / `phone` đồng bộ từ user (có thể bổ sung sau các trường chuyên môn: bằng lái, hết hạn…).
2. **Xe có tài xế mặc định tùy chọn**: `vehicles.default_driver_id` → `drivers.id` (nullable). Phân công chuyến (`trips`) vẫn có `driver_id` riêng; tài xế mặc định dùng cho điều phối nhanh và màn hình quản lý.
3. **Đối tác / nhà cung cấp**: dùng bảng sẵn có `transport_providers` (NCC vận chuyển), không trùng với bảng `users`.

## 2. Sơ đồ quan hệ (rút gọn)

```
users 1 ──0..1 drivers (user_id)
drivers 1 ──* trips (driver_id)
vehicles * ──0..1 drivers (default_driver_id)
transport_providers 1 ──* trips (transport_provider_id) [đã có ở Trip]
```

## 3. Bảng & cột liên quan

### 3.1 `users` (có sẵn)

- Định danh nội bộ: `name`, `email`, `phone`, `employee_code`, `avatar_url`, `is_active`.

### 3.2 `drivers` (có sẵn)

| Cột | Ý nghĩa |
|-----|--------|
| `user_id` | FK → `users.id` (nullable trong migration cũ; khi gán tài xế từ user thì **luôn set**) |
| `full_name`, `phone` | Đồng bộ từ user khi gán / cập nhật |
| `license_class`, `license_expires_at` | Bổ sung sau (quản lý giấy phép lái) |
| `employment_status` | `active` / `on_leave` / `terminated` |
| `availability_status` | `available` / `busy` / `offline` |

**Quy tắc**: một user chỉ nên có **tối đa một** bản ghi `drivers` có `user_id` trùng (nên unique ở DB khi dữ liệu sạch).

### 3.3 `vehicles` (có sẵn + mở rộng)

| Cột | Ý nghĩa |
|-----|--------|
| `license_plate` | Biển số (hiển thị như “mã xe”) |
| `type`, `seat_count`, `payload_kg` | Loại / sức chứa |
| `status` | `ready`, `in_use`, `maintenance`, `broken` |
| `insurance_expires_at`, `inspection_expires_at` | Tuân thủ BH / kiểm định (map UI: BH / ĐK) |
| **`default_driver_id`** (mới) | FK → `drivers.id`, nullable |

### 3.4 `transport_providers` (có sẵn)

Đối tác vận chuyển: `name`, `type`, `contact_name`, `contact_phone`, `is_active`, …

## 4. Quyền (RBAC)

| Permission | Dùng cho |
|------------|----------|
| `resource.vehicle.manage` | CRUD cập nhật xe (gồm gán `default_driver_id`) |
| `resource.driver.manage` | Gán tài xế từ user (`POST /drivers/from-user`), xem danh sách tài xế |
| `resource.provider.manage` | Danh sách / quản lý đối tác (list NCC) |
| `trip.assign` | Được phép xem tài nguyên vận hành (xe, tài xế) như dispatcher |

API dùng `permission:any,...` khi cần nhiều quyền hợp lệ.

## 5. API (REST, prefix `/api`, `auth:sanctum`)

### 5.1 Đã có / mở rộng

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/vehicles` | Danh sách xe (có `default_driver` + `user` lồng nhau khi có) |
| GET | `/drivers` | Danh sách tài xế (có `user`) |
| GET | `/transport-providers` | Danh sách đối tác (NCC) |

Query chung (tùy endpoint): `per_page`, lọc theo trạng thái nếu có.

### 5.2 Mới

| Method | Path | Body / query | Permission |
|--------|------|----------------|-------------|
| GET | `/users/for-driver-assignment` | `q` (tối thiểu 2 ký tự) | `resource.driver.manage` **hoặc** `trip.assign` |
| POST | `/drivers/from-user` | `{ "user_id": <id> }` | `resource.driver.manage` hoặc `trip.assign` |
| POST | `/vehicles` | Tạo xe: `license_plate` (bắt buộc, unique), `type`, `seat_count`, `payload_kg`, `status`, `odometer_km`, `inspection_expires_at`, `insurance_expires_at`, `default_driver_id` | `resource.vehicle.manage` |
| PATCH | `/vehicles/{vehicle}` | Cập nhật các trường trên (tùy chọn). User chỉ có `trip.assign` chỉ được gửi `default_driver_id`. | `resource.vehicle.manage` (đầy đủ) hoặc `trip.assign` (chỉ tài xế mặc định) |

**Luồng gán tài xế từ user**

1. UI gọi `GET /users/for-driver-assignment?q=...` để chọn user.
2. `POST /drivers/from-user` với `user_id` → `updateOrCreate` `drivers` theo `user_id`, đồng bộ `full_name`, `phone` từ `users`.
3. (Tuỳ chọn) `PATCH /vehicles/{id}` để gán tài xế mặc định cho xe.

Response thành công theo convention: `{ "data": ... }` (xem `docs/API_CONVENTIONS.md`).

## 6. Gợi ý mở rộng (chưa bắt buộc)

- **Optimistic locking** cho `PATCH /vehicles/{id}` nếu nhiều người sửa cùng lúc (`lock_version` trên `vehicles`).
- **Bảng `vehicle_documents`** nếu cần nhiều file / loại giấy tờ thay vì chỉ hai ngày hết hạn trên xe.
- **Audit**: log `driver.linked_from_user`, `vehicle.default_driver_changed`.

## 7. Seed demo

`DemoFlowSeeder` có user `taixe@va.local` và một `Driver` gắn `user_id`; có thể gán `default_driver_id` cho một `Vehicle` để kiểm tra UI.

## 8. Trạng thái triển khai (codebase)

- Migration `2026_04_13_100000_add_default_driver_id_to_vehicles_table.php`: thêm `vehicles.default_driver_id`.
- `OperationalResourceController`: `GET /vehicles`, `POST /vehicles`, `GET /drivers`, `GET /transport-providers`, `POST /drivers/from-user`, `PATCH /vehicles/{vehicle}`.
- `UserSearchForDriverAssignmentController`: `GET /users/for-driver-assignment?q=`.
- Frontend `ResourcesListView.vue` + `resources/js/src/api/operational.js` gọi các endpoint trên; chạy `php artisan migrate` (cần MySQL) trước khi dùng.
