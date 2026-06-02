# PROJECT_STRUCTURE — VA Điều Vận

## 📑 Mục lục

- [Tổng quan thư mục gốc](#tổng-quan-thư-mục-gốc)
- [Backend Laravel](#backend-laravel)
- [Frontend Vue (`resources/js/src`)](#frontend-vue-resourcesjssrc)
- [Routes](#routes)
- [Convention đặt tên](#convention-đặt-tên)
- [Kiến trúc áp dụng trong repo](#kiến-trúc-áp-dụng-trong-repo)

---

## Tổng quan thư mục gốc

```
va-dieuvan/
├── app/                    # Code ứng dụng Laravel
├── bootstrap/              # Bootstrap framework
├── config/                 # Cấu hình Laravel + dispatch/push/permission
├── database/
│   ├── factories/
│   ├── migrations/         # ~80 migration định nghĩa schema
│   └── seeders/
├── docs/                   # Tài liệu dự án (bộ file .md)
├── public/                 # Web root, asset build
├── resources/
│   ├── css/
│   ├── js/                 # Entry Vite + SPA Vue
│   └── views/              # Blade shell + PDF templates
├── routes/
│   ├── api.php             # Entry API + nhóm Sanctum
│   ├── web.php             # OAuth + SPA fallback + sw.js
│   └── api/spa/           # File route SPA đã tách module
├── scripts/                # PowerShell / PHP tiện ích dev
├── storage/
├── tests/
├── artisan
├── composer.json
├── package.json
├── vite.config.js
└── tsconfig.json           # Type-check TS + Vue SFC
```

---

## Backend Laravel

### `app/Http/Controllers`


| Khu vực        | Đường dẫn                       | Vai trò                                                    |
| -------------- | ------------------------------- | ---------------------------------------------------------- |
| Auth web OAuth | `Auth/GoogleAuthController.php` | Redirect Google                                            |
| API Auth       | `Api/Auth/AuthController.php`   | Login/logout Sanctum                                       |
| SPA domain     | `Api/**`                        | REST JSON — ~48 file trong `Api/` (trips, cargo, portal, p2p, admin, …) |
| Trait response | `Api/Concerns/ApiResponses.php` | Chuẩn hoá JSON                                             |


> Không có Repository layer riêng — truy vấn chủ yếu trong Controller/Service/Model.

### `app/Http/Middleware` (alias trong `Kernel.php`)


| Alias            | Class                       | Ý nghĩa                                  |
| ---------------- | --------------------------- | ---------------------------------------- |
| `dispatch.web`   | `EnsureDispatchWebAccess`   | Cho phép vào SPA điều vận / driver shell |
| `dispatch.staff` | `EnsureDispatchStaffAccess` | superadmin, admin, dispatcher, department_head |
| `driver.spa`     | `EnsureDriverWebAccess`     | Luồng chỉ tài xế                         |
| `permission`     | `EnsureHasPermission`       | Spatie permission                        |
| `role`           | `EnsureHasRole`             | Spatie role                              |
| `idempotency`    | `IdempotencyKey`            | Tránh gọi trùng POST                     |
| `feature`        | `EnsureFeatureEnabled`      | Feature toggle                           |


### `app/Services` (~37 file PHP)

Ví dụ: `Dispatching/DispatchingService.php`, `P2pPolicy/`, `RecurringDispatch/`, `Auditing/AuditLogger.php`, `WebPushSender.php`, `Costs/CostCalculationService.php`, `Admin/UserRoleService.php`, `Operational/DriverWorkloadService.php`, `Ocr/PaperOcrStubService.php`, …

### `app/Models`

46 model domain + User — mapping Eloquent tới bảng migrations.

### `app/Notifications`

`TripAssignedNotification`, `NewDispatchRequestNotification`, `CargoSlaBreachedNotification` — channel `database`, một số `ShouldQueue`.

### `app/Listeners`

`SendWebPushOnDatabaseNotification` — lắng `NotificationSent`.

### `app/Jobs`

3 job class tùy chỉnh:

- `ProcessAttachmentOcrJob` — OCR attachment nền
- `PolicyStudentImportJob` — import học sinh P2P
- `PolicyGenerateTripsBatchJob` — batch sinh chuyến từ policy

Ngoài ra workload nền qua **Notification implements ShouldQueue** — xem [QUEUE_EVENT_CRON.md](./QUEUE_EVENT_CRON.md).

### Policies / Requests

- **Policies**: tra cứu trong `app/Policies/` (nếu có) và `AuthServiceProvider` — *đối chiếu grep `policy` khi mở rộng tài liệu*.
- **FormRequest**: `app/Http/Requests/` — validate + authorize cho API.

### `config/`

Quan trọng: `dispatch.php`, `push.php`, `permission.php`, `queue.php`, `services.php` (Google).

---

## Frontend Vue (`resources/js/src`)

```
resources/js/
├── app.js                 # import bootstrap + src/main
├── bootstrap.js           # axios global
└── src/
    ├── main.js            # Pinia, router, i18n, PWA, restoreSession
    ├── App.vue
    ├── router/index.js    # Staff base path + driver routes + guards
    ├── i18n.js + locales/
    ├── api/               # 20 module gọi REST (axios)
    ├── store/             # Pinia chính: auth, ui, driverDashboard, notificationCenter
    ├── stores/            # Pinia TS: userRoleAssignmentStore.ts
    ├── services/          # TS service layer (vd userRoleService)
    ├── composables/       # Logic tái sử dụng (.js / .ts)
    ├── components/        # UI layout/nav/ui/dispatch/driver/trips/portal/...
    ├── views/             # Pages/screens (staff, driver, portal)
    ├── core/              # http, monitoring, offline/outbox
    ├── config/            # dispatchWebBase, nav, shortcuts
    ├── util/
    ├── types/
    ├── pwa/registerSW.js
    └── sw.js              # Service worker (injectManifest)
```

**Quy ước trong `.cursorrules`**: Component → Store → Service (không gọi API trực tiếp trong component nếu có thể qua store/service).

---

## Routes


| File                                       | Nội dung                                                          |
| ------------------------------------------ | ----------------------------------------------------------------- |
| `routes/web.php`                           | `/auth/google`, `/login`, `/sw.js`, fallback SPA                  |
| `routes/api.php`                           | `/api/login`, telemetry, `/api/user`, **`/api/portal/*`**, nhóm `dispatch.web` require spa |
| `routes/api/spa/common-read.php`           | Đọc chung                                                         |
| `routes/api/spa/common-mutate.php`         | Ghi profile + dispatch-request (staff/internal)                   |
| `routes/api/spa/driver-read.php`           | Đọc driver                                                        |
| `routes/api/spa/driver-mutate.php`         | Ghi trip ops costs attachments                                    |
| `routes/api/spa/dispatch-staff-read.php`   | Staff đọc (admin, dispatcher, department_head)                    |
| `routes/api/spa/dispatch-staff-mutate.php` | Staff ghi + P2P policy                                            |


Prefix API thực tế: Laravel chuẩn — các route trong `routes/api.php` được mount với prefix `/api` (trừ khi đổi `RouteServiceProvider`).

---

## Convention đặt tên


| Phạm vi        | Quy ước                                                       |
| -------------- | ------------------------------------------------------------- |
| PHP class      | `PascalCase`, namespace PSR-4 `App\`                          |
| DB tables      | `snake_case` plural                                           |
| API routes     | `kebab-case` URI segments (`dispatch-requests`, `trip-costs`) |
| Vue SFC        | `PascalCase.vue`                                              |
| Composables    | `useThing.js` / `useThing.ts`                                 |
| Pinia store id | string `'auth'`, `'ui'`, …                                    |
| Permissions    | `dot.lowercase` (`trip.assign`, `system.user_roles.manage`)   |


---

## Kiến trúc áp dụng trong repo

1. **Modular monolith** — không microservice.
2. **Thin controller** — đẩy logic vào Service (`DispatchingService`, …).
3. **SPA + token API** — Sanctum PAT; không Inertia.
4. **RBAC + Feature flags** — song song middleware backend và meta route frontend.

Scaffold module mới: xem [scripts/New-ApiModule.ps1](../scripts/New-ApiModule.ps1) và [scripts/README.md](../scripts/README.md).

---

## Liên quan

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [API_OVERVIEW.md](./API_OVERVIEW.md)

