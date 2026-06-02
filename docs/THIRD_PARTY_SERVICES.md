# THIRD_PARTY_SERVICES — VA Điều Vận

## 📑 Mục lục

- [Google OAuth (Socialite)](#google-oauth-socialite)
- [Web Push VAPID](#web-push-vapid)
- [DomPDF](#dompdf)
- [CMS database (đọc user)](#cms-database-đọc-user)
- [AWS S3 (tuỳ chọn)](#aws-s3-tuỳ-chọn)
- [Frontend libraries](#frontend-libraries)
- [Dịch vụ không có trong source](#dịch-vụ-không-có-trong-source)

---

## Google OAuth (Socialite)

| | |
|--|--|
| **Purpose** | Đăng nhập nhanh cho user có email domain được phép |
| **Package** | `laravel/socialite` |
| **Auth method** | OAuth2 Authorization Code |
| **Config** | `config/services.php` → `google` + env `GOOGLE_*` |
| **Flow** | `GET /auth/google` → Google → `GET /auth/google/callback` → tạo/cập nhật user → `createToken('web')` → redirect `/login?token=...&redirect=...` |

**Security**

- Whitelist domain: `GOOGLE_ALLOWED_EMAIL_DOMAINS` (CSV) — nếu rỗng, **không** cho tạo user mới qua Google (theo `GoogleAuthController`).
- Kiểm tra `is_active` và `canAccessDispatchWebApp`/`DriverWebApp` sau khi có user.

**Retry / error**

- Lỗi Socialite → redirect login kèm `error` query.

---

## Web Push VAPID

| | |
|--|--|
| **Purpose** | Đẩy thông báo PWA khi có notification `database` |
| **Package** | `minishlink/web-push` |
| **Auth** | VAPID keys |
| **Config** | `config/push.php`, env `VAPID_*` |

**Flow**

1. SPA `GET /api/push/vapid-public-key`
2. Subscribe trong browser → `POST /api/push/subscriptions`
3. Backend gửi payload JSON qua `WebPushSender` sau `NotificationSent`

**Retry / error**

- Flush theo subscription; log `subscription_expired` / `send_failed`.
- Không có retry queue riêng — lỗi mạng có thể mất push đơn lẻ.

---

## DomPDF

| | |
|--|--|
| **Purpose** | Xuất PDF phiếu điều xe (`export-pdf`) |
| **Package** | `barryvdh/laravel-dompdf` |
| **Template** | `resources/views/pdf/dispatch-request.blade.php` |

**Security**

- Endpoint có throttle; chỉ user đã auth Sanctum + trong nhóm dispatch SPA.

---

## CMS database (đọc user)

| | |
|--|--|
| **Purpose** | Đồng bộ user từ hệ thống CMS (`cms:sync-users`) |
| **Connection** | `cms` — env `CMS_DB_*` |
| **Auth DB** | User/pass MySQL read/write theo quyền triển khai |

**Error handling**

- Command fail nếu không kết nối được — kiểm tra log CLI.

---

## AWS S3 (tuỳ chọn)

| | |
|--|--|
| **Purpose** | Filesystem cloud cho attachment khi `FILESYSTEM_DISK=s3` |
| **Config** | `AWS_*` trong `.env`, `config/filesystems.php` |

**Security**

- IAM least privilege; không commit access key.

---

## OpenSpout (Excel)

| | |
|--|--|
| **Purpose** | Import/export danh sách học sinh P2P (`.xlsx`) |
| **Package** | `openspout/openspout` |
| **API liên quan** | `POST /api/p2p-policy/students/import*`, `GET .../export`, `GET .../import-template` |
| **Job** | `PolicyStudentImportJob` — xử lý commit import nền |

**Security**

- Chỉ user có `p2p_policy.import_export` hoặc `p2p_policy.manage` (theo route).
- Validate file type/size trong FormRequest trước khi dispatch job.

---

## Frontend libraries

| Thư viện | Purpose | Ghi chú |
|----------|---------|---------|
| Leaflet | Bản đồ | CDN/asset local tuỳ build |
| ECharts | Biểu đồ dashboard | Split chunk Vite |
| Axios | HTTP client | Bearer token từ store |
| Workbox | PWA cache | `vite-plugin-pwa` |

---

## Dịch vụ không có trong source

> **SMS gateway:** Không thấy package/provider SMS trong `composer.json`.

> **Payment gateway (VNPay/Stripe/MoMo):** Không thấy SDK thanh toán — `payments` là trạng thái nghiệp vụ nội bộ.

> **Firebase:** Không thấy dependency Firebase.

> **OAuth providers khác Google:** Chỉ cấu hình Google trong `config/services.php`.

---

## Liên quan

- [ENVIRONMENT_SETUP.md](./ENVIRONMENT_SETUP.md)
- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
