# Tiếp tục xây dựng dự án

Tài liệu ngắn: **chỉ trỏ đến file**, không nhân bản nội dung SRS hay toàn bộ route.

## Stack

- Backend: Laravel 10, PHP 8.1+, Sanctum (`Bearer` token lưu `localStorage`), Spatie Permission.
- Frontend: Vue 3, Vue Router, Pinia, Axios, Tailwind.

## Cấu trúc thư mục quan trọng

```
app/Http/
  Controllers/Api/   # API JSON
  Requests/Api/      # Validation — ưu tiên dùng thay vì validate trong controller
  Middleware/        # dispatch.web, dispatch.staff, driver.spa, …
routes/
  api.php            # Cổng chính + group Sanctum
  api/spa/           # common-read / common-mutate, driver-*, dispatch-staff-*
resources/js/src/
  router/index.js    # Route SPA + meta.featureKey
  store/             # Pinia (auth, ui, …) — lưu ý: tên thư mục là store/, không phải stores/
  api/               # Gọi HTTP theo module
  core/http/         # createHttpClient.js — base URL + Authorization
  views/             # Màn hình
  composables/
config/feature.php   # Bật/tắt module (middleware feature)
```

## Luồng API (backend)

1. **Public**: `POST /api/login`, telemetry (xem `routes/api.php`).
2. **Đã đăng nhập** (`auth:sanctum`):
   - Toàn bộ SPA chạy trong middleware `dispatch.web`.
   - **Đọc** (ít overhead): `common-read.php`, và theo role `driver-read.php` hoặc `dispatch-staff-read.php` — **không** gắn `LogApiActivity`.
   - **Ghi**: `common-mutate.php`, `driver-mutate.php`; điều vận thêm `dispatch-staff-mutate.php` — có `LogApiActivity` + throttle riêng.

Khi thêm endpoint mới: chọn đúng file trong `routes/api/spa/` và nhóm middleware (read vs mutate, staff vs driver) để **không** log nhầm hoặc mở quyền sai.

**Không** dùng prefix `/api/v1` trong repo này; API nằm dưới prefix cấu hình của Laravel (`/api/...`).

## Luồng frontend

1. Token: `resources/js/src/core/config/authKeys.js` (`TOKEN_KEY`), gắn header trong `createHttpClient.js`.
2. Router: thêm `path` + `meta` (title, `featureKey` nếu cần khóa theo config).
3. Gọi API: ưu tiên thêm hàm trong `resources/js/src/api/<module>.js` dùng client từ `api/http.js` (nếu project đã bọc sẵn).

## Việc thường làm

| Việc | File/folder bắt đầu |
|------|---------------------|
| Thêm API | `routes/api/spa/*.php` → Controller → `Requests/Api/...` |
| Thêm bảng DB | `database/migrations/` |
| Phân quyền | Policy + Permission/Role (Spatie) — xem middleware `EnsureDispatchStaffAccess`, `EnsureDriverWebAccess` |
| Feature toggle | `config/feature.php` + middleware `feature` |
| Màn hình mới | `views/...` + `router/index.js` |

## Kiểm thử

- `tests/Feature/` cho API; chạy tập trung một class test liên quan thay vì full suite khi sửa nhỏ.

## SRS / nghiệp vụ

Giữ SRS ở ngoài repo hoặc link; khi làm một user story, **trích đúng mục FR/UC** vào ticket/issue thay vì dán cả tài liệu vào chat.

## Script hỗ trợ module mới

Xem [scripts/README.md](../scripts/README.md): `dev-bootstrap.ps1`, `New-ApiModule.ps1`, `module-gap-report.php`.
