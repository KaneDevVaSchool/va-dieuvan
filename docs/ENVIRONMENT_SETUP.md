# ENVIRONMENT_SETUP — VA Điều Vận

## 📑 Mục lục

- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt local (từng bước)](#cài-đặt-local-từng-bước)
- [Biến môi trường](#biến-môi-trường)
- [Database migrate & seed](#database-migrate--seed)
- [Frontend build](#frontend-build)
- [Queue & scheduler](#queue--scheduler)
- [Mail & storage](#mail--storage)
- [SSL / HTTPS](#ssl--https)
- [Deploy staging / production (checklist)](#deploy-staging--production-checklist)
- [Script tiện ích](#script-tiện-ích)

---

## Yêu cầu hệ thống


| Thành phần | Phiên bản / ghi chú                                                                                                                          |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| PHP        | **^8.1** (`composer.json`) — extension: mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath                                          |
| Composer   | 2.x                                                                                                                                          |
| Node.js    | **18+** khuyến nghị (Vite 5)                                                                                                                 |
| npm        | đi kèm Node                                                                                                                                  |
| Database   | **MySQL** (mặc định Laravel migrate có SQL không-SQLite-specific trong một số service — ví dụ `DispatchingService` COALESCE MySQL vs SQLite) |
| Redis      | **Tùy chọn** — khuyến nghị cho queue/cache production                                                                                        |


Tham chiếu file gốc: [.env.example](../.env.example), [composer.json](../composer.json), [package.json](../package.json).

---

## Cài đặt local (từng bước)

### Bước 1 — Clone repository

```bash
git clone <repository-url> va-dieuvan
cd va-dieuvan
```

### Bước 2 — PHP dependencies

```bash
composer install
```

### Bước 3 — File môi trường

```powershell
copy .env.example .env
php artisan key:generate
```

Chỉnh `DB_*` và các khóa tích hợp (Google, VAPID) — xem bảng dưới.

### Bước 4 — Database

Tạo database MySQL trống, ví dụ `va_dieuvan`, và đặt trong `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=va_dieuvan
DB_USERNAME=root
DB_PASSWORD=secret
```

Chạy migration:

```bash
php artisan migrate
```

### Bước 5 — Dữ liệu mẫu / RBAC

```bash
php artisan db:seed
```

`DatabaseSeeder` gọi: `RbacSeeder`, `TainpDriverSeeder`, `FeatureToggleSeeder`, `ReferencePricingSeeder`, `DemoFlowSeeder`, `VehicleExcelSeeder` — xem [database/seeders/DatabaseSeeder.php](../database/seeders/DatabaseSeeder.php).

Superadmin email (tuỳ chọn): cấu hình trong `config/permission.php` key `superadmin_email` — `RbacSeeder` gán role `superadmin` cho user có email khớp nếu tồn tại.

### Bước 6 — Storage link

```bash
php artisan storage:link
```

### Bước 7 — Frontend dependencies & dev server

```bash
npm install
npm run dev
```

Terminal khác — Laravel:

```bash
php artisan serve
```

Mặc định SPA load qua `APP_URL` (ví dụ `http://127.0.0.1:8000`). Trong `.env.example` có gợi ý `VITE_APP_URL` khi chạy Vite dev proxy `/storage`.

### Bước 8 — Build production asset (local kiểm tra)

```bash
npm run build
```

Output Vite: `public/build/` (gitignored).

---

## Biến môi trường


| Biến                                                                                              | Mục đích                                                              |
| ------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`                                          | Cơ bản Laravel                                                        |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_EMAIL_DOMAINS` | OAuth Google + whitelist domain email                                 |
| `DB_*`                                                                                            | MySQL chính                                                           |
| `CMS_DB_DATABASE` (+ optional host/user/pass)                                                     | DB thứ hai đọc CMS (`CmsUserInfoService`, command sync)               |
| `CACHE_DRIVER`, `SESSION_DRIVER`                                                                  | Cache/session                                                         |
| `QUEUE_CONNECTION`                                                                                | `**sync**` local (job chạy ngay); production: `redis` hoặc `database` |
| `REDIS_*`                                                                                         | Redis nếu dùng                                                        |
| `MAIL_*`                                                                                          | SMTP / Mailpit dev                                                    |
| `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`                                          | Web Push                                                              |
| `DISPATCH_NOTIFICATIONS_QUEUE_DEFAULT`, `DISPATCH_NOTIFICATIONS_QUEUE_URGENT`                     | Tên queue thông báo                                                   |
| `DISPATCH_MAIL_HELPDESK`                                                                          | Chuỗi hiển thị / mailto ở footer email chờ duyệt Trưởng đơn vị          |
| `DISPATCH_DEPT_APPROVAL_REMIND_AFTER_HOURS`                                                       | Số giờ sau fill giá trước khi cron nhắc Trưởng BP (`dispatch:remind-dept-approvals`) |
| `DISPATCH_DEBUG_NOTIFICATION_LOG`                                                                 | Log chi tiết luồng notify                                             |
| `DISPATCH_PASSENGER_URGENT_HOURS`, `DISPATCH_CARGO_URGENT_HOURS`                                  | Ngưỡng “urgent” fallback                                              |
| `AWS_*`                                                                                           | S3 nếu dùng                                                           |
| `VITE_*`                                                                                          | Biến expose cho frontend build                                        |


**Tạo VAPID keys:**

```bash
npx --yes web-push generate-vapid-keys
```

---

## Database migrate & seed

```bash
# Chỉ migrate
php artisan migrate

# Migrate fresh + seed (xóa toàn bộ dữ liệu)
php artisan migrate:fresh --seed

# Seed riêng RBAC
php artisan db:seed --class=RbacSeeder
```

---

## Frontend build


| Lệnh            | Mô tả                                        |
| --------------- | -------------------------------------------- |
| `npm run dev`   | Vite HMR                                     |
| `npm run build` | Production bundle + PWA `public/build/sw.js` |


---

## Queue & scheduler

### Queue worker (khi `QUEUE_CONNECTION` ≠ `sync`)

```bash
php artisan queue:work --queue=default,urgent-notifications
```

Với driver `database`, cần migration có bảng `jobs` (có migration `2026_05_08_140000_create_jobs_table.php`) và `failed_jobs` (Laravel mặc định).

### Scheduler (cron)

Trong `app/Console/Kernel.php` có:

```php
$schedule->command('cargo:sla-check')->everyFiveMinutes();
```

Trên server Linux, thêm **một dòng** crontab user chạy PHP:

```cron
* * * * * cd /path/to/va-dieuvan && php artisan schedule:run >> /dev/null 2>&1
```

Windows dev: chạy thủ công `php artisan cargo:sla-check` hoặc Task Scheduler gọi `schedule:run`.

---

## Mail & storage

- **Mail dev**: `.env.example` trỏ Mailpit `MAIL_HOST=mailpit`, `MAIL_PORT=1025`. Nhiều notification dùng thêm kênh **mail** (chờ/duyệt Trưởng BP, kết quả duyệt cho người đề xuất, phiếu **gấp** cho điều vận, gán chuyến tài xế, nhắc duyệt cron) — cần `MAIL_*` và **`php artisan queue:work`** khi queue không phải `sync`. Tuỳ chọn: **`DISPATCH_MAIL_HELPDESK`**, **`DISPATCH_DEPT_APPROVAL_REMIND_AFTER_HOURS`** trong `config/dispatch.php`.
- **Filesystem**: `FILESYSTEM_DISK=local` + `storage/app/public`; nhớ `php artisan storage:link`.

---

## SSL / HTTPS

- Production: terminate TLS tại Nginx / load balancer (xem [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)).
- Local HTTPS: tùy stack (Valet, Herd, mkcert) — không bắt buộc cho Sanctum token nếu chỉ gọi API qua HTTP localhost.

---

## Deploy staging / production (checklist)


| Bước                    | Lệnh / hành động                                            |
| ----------------------- | ----------------------------------------------------------- |
| Env                     | `APP_ENV=production`, `APP_DEBUG=false`, khóa bí mật đầy đủ |
| Composer                | `composer install --no-dev --optimize-autoloader`           |
| Migrate                 | `php artisan migrate --force`                               |
| Cache config/route/view | `php artisan config:cache`, `route:cache`, `view:cache`     |
| Assets                  | `npm ci && npm run build`                                   |
| Queue                   | Supervisor `queue:work`                                     |
| Cron                    | `schedule:run` mỗi phút                                     |
| Permissions storage     | `chmod -R ug+rwx storage bootstrap/cache`                   |


Chi tiết Nginx/Supervisor: [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md).

---

## Script tiện ích

PowerShell bootstrap (Windows):

```powershell
.\scripts\dev-bootstrap.ps1
.\scripts\dev-bootstrap.ps1 -Migrate
```

Xem [scripts/README.md](../scripts/README.md).

---

## Liên quan

- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)
- [QUEUE_EVENT_CRON.md](./QUEUE_EVENT_CRON.md)
- [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

