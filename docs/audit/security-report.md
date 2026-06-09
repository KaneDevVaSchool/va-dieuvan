# Báo cáo Bảo mật — VA Điều Vận

**Ngày audit**: 2026-06-10

---

## Tóm tắt

| Mức độ | Số vấn đề |
|--------|----------|
| CRITICAL | 0 |
| HIGH | 1 |
| MEDIUM | 3 |
| LOW | 5 |
| INFO | 4 |

---

## HIGH

### SEC-H1: Mass Assignment Protection cần kiểm tra User model
**File**: `app/Models/User.php:22-34`  
**Vấn đề**: `$fillable` bao gồm `primary_role_name` và `primary_role_id`. Nếu có endpoint nào cho phép user tự update profile mà không filter fields này, user có thể tự gán role.  
**Kiểm tra**: Rà soát tất cả controller dùng `$user->update($request->all())` hoặc `fill()`.  
**Giải pháp**: Đảm bảo update profile endpoint chỉ cho phép: `name`, `phone`, `avatar_url`.  
**Ưu tiên**: P1

---

## MEDIUM

### SEC-M1: Thiếu rate limiting cho Google Auth callback
**File**: `routes/web.php` — Google auth routes  
**Vấn đề**: Endpoint `/auth/google/callback` không có throttle middleware. Một số trường hợp brute-force OAuth flow có thể exploit.  
**Giải pháp**: Thêm `throttle:10,1` cho auth routes.

### SEC-M2: Idempotency key không có expiration cleanup
**File**: `app/Models/IdempotentRequest.php`  
**Vấn đề**: `idempotent_requests` table có thể tích lũy dữ liệu vô thời hạn. Dữ liệu cũ không cần thiết là attack surface tiềm năng (enumeration).  
**Giải pháp**: Thêm scheduled command để xóa records cũ hơn 7 ngày.

### SEC-M3: PDF generation với DomPDF — XSS trong template
**File**: `resources/views/dispatch-request.blade.php` và các template PDF  
**Vấn đề**: Nếu dữ liệu người dùng được render vào PDF template không được escape đúng cách, có thể dẫn đến XSS trong PDF hoặc nội dung inject.  
**Giải pháp**: Đảm bảo tất cả biến trong Blade PDF template dùng `{{ }}` (auto-escape) không phải `{!! !!}`.

---

## LOW

### SEC-L1: Token Sanctum không có expiration
**Vấn đề**: Laravel Sanctum tokens mặc định không expire. Nếu token bị lộ, attacker có thể dùng mãi.  
**Giải pháp**: Cấu hình `sanctum.expiration` trong `config/sanctum.php`.

### SEC-L2: CORS config cần review
**File**: `config/cors.php`  
**Vấn đề**: Cần xác nhận `allowed_origins` không quá rộng trong production.

### SEC-L3: File upload thiếu magic bytes validation
**File**: `app/Http/Controllers/Api/Attachments/AttachmentController.php`  
**Vấn đề**: Cần kiểm tra file upload có validate MIME type thực sự (magic bytes) hay chỉ extension.  
**Giải pháp**: Dùng `finfo_file()` hoặc `Storage::mimeType()` để validate.

### SEC-L4: Push subscription endpoint không validate origin
**File**: `app/Http/Controllers/Api/Trips/PushSubscriptionController.php`  
**Vấn đề**: Push subscription có thể được tạo từ bất kỳ auth user nào.

### SEC-L5: Audit log không capture IP address nhất quán
**File**: `app/Services/Auditing/AuditLogger.php`  
**Vấn đề**: `ip_address` field không phải lúc nào cũng được set.

---

## INFO (Tốt — cần duy trì)

### SEC-I1: ✅ CSRF Protection
`VerifyCsrfToken` middleware active. API routes dùng token auth (không cần CSRF).

### SEC-I2: ✅ SQL Injection Protection
Toàn bộ code dùng Eloquent ORM và Query Builder với parameterized queries. Không phát hiện raw SQL injection risk.

### SEC-I3: ✅ Authorization checks nhất quán
`abort_unless(TripVisibility::...)` và `EnsureHasPermission` middleware được dùng đúng.

### SEC-I4: ✅ Optimistic Locking
`TripOptimisticLock` và `attendance_lock_version` bảo vệ chống concurrent write conflicts.

---

## Checklist bảo mật chưa thực hiện

- [ ] Laravel Pint không check security issues
- [ ] Không có Dependabot hoặc `composer audit` trong CI
- [ ] Chưa có Content Security Policy header
- [ ] Chưa có security.txt
- [ ] Chưa kiểm tra các biến .env không nên commit (API keys, etc.)
