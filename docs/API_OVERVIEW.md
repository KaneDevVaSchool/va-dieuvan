# API_OVERVIEW — VA Điều Vận

## 📑 Mục lục

- [Quy ước chung](#quy-ước-chung)
- [Authentication](#authentication)
- [Telemetry](#telemetry)
- [Nhóm route SPA](#nhóm-route-spa)
- [Internal / helper](#internal--helper)
- [Third-party (OAuth web)](#third-party-oauth-web)
- [Implementation chưa gắn route](#implementation-chưa-gắn-route)

---

## Quy ước chung

| Hạng mục | Chi tiết |
|----------|-----------|
| Base URL | `{APP_URL}/api` — Laravel mặc định prefix `api` từ `RouteServiceProvider` |
| Format | JSON `Content-Type: application/json` |
| Auth | `Authorization: Bearer {token}` cho nhóm `auth:sanctum` |
| Idempotency | Một số `POST` có middleware `idempotency` — gửi header key theo implementation middleware (thường `Idempotency-Key`) |
| Lỗi | Validation 422; abort business 403/409/404 — message tiếng Việt trong một số controller |

**Middleware stack (tóm tắt)**

1. Toàn API: `api` group (throttle mặc định `ThrottleRequests:api` trong Kernel — không liệt kê chi tiết tại đây).
2. `auth:sanctum` — các endpoint SPA.
3. `dispatch.web` — user được vào app điều vận/tài xế.
4. `dispatch.staff` — chỉ admin/dispatcher/superadmin (không phải pure driver-only account).
5. `driver.spa` — chỉ tài xế.
6. `LogApiActivity` — các nhóm **mutate** ghi log hoạt động.

Source: [routes/api.php](../routes/api.php), [routes/api/spa/*.php](../routes/api/spa/).

---

## Authentication

### `POST /api/login`

| | |
|--|--|
| Auth | Public |
| Throttle | `20,1` |
| Body | `email`, `password`, optional `device_name` |

**Success (rút gọn):** `{ token, token_type: Bearer, user: { ..., permissions: [], is_superadmin, feature_toggles, feature_toggle_states } }`  
**Error:** 422 validation; 403 inactive / không có quyền vào app.

### `POST /api/logout`

| | |
|--|--|
| Auth | Sanctum |
| Throttle | `30,1` |

---

## Telemetry

### `POST /api/telemetry/frontend`

| | |
|--|--|
| Auth | Public |
| Throttle | `60,1` |

Payload theo `ClientTelemetryController` — xem file controller để biết schema chính xác.

---

## Nhóm route SPA

Tất cả endpoint dưới đây yêu cầu **`auth:sanctum`** + **`dispatch.web`** trừ khi ghi chú khác.

### A. Đọc chung (`common-read.php`)

| Method | Path | Ghi chú |
|--------|------|---------|
| GET | `/user` | Profile |
| GET | `/push/vapid-public-key` | Web Push |
| POST | `/push/subscriptions` | throttle `20,1` |
| DELETE | `/push/subscriptions` | throttle `20,1` |
| GET | `/notifications/inbox` | |
| POST | `/notifications/read-all` | |
| POST | `/notifications/{notification}/read` | UUID |
| GET | `/dispatch-requests/{dispatchRequest}` | |
| GET | `/dispatch-requests/{dispatchRequest}/export-pdf` | throttle `30,1` |
| GET | `/trips` | |
| GET | `/trips/stats` | |
| GET | `/trips/{trip}` | |
| GET | `/trip-costs` | |
| GET | `/trip-costs/{tripCost}` | |
| GET | `/trips/{trip}/costs` | |
| GET | `/cargo-shipments` | |
| GET | `/cargo-shipments/{cargoShipment}/timeline` | |
| GET | `/cargo-shipments/{cargoShipment}` | |
| GET | `/routes` | |
| GET | `/routes/{route}` | |
| GET | `/nav/badges` | throttle `60,1` |
| GET | `/attachments/{attachment}/download` | throttle `120,1`, `{attachment}` numeric |

### B. Ghi chung (`common-mutate.php`) — có `LogApiActivity`

| Method | Path |
|--------|------|
| PATCH | `/user` |

### C. Đọc tài xế (`driver-read.php`) — thêm `driver.spa`

| Method | Path |
|--------|------|
| GET | `/driver/summary` |
| GET | `/driver/trips` |

### D. Ghi tài xế / ops (`driver-mutate.php`) — `LogApiActivity`

| Method | Path | Middleware đặc biệt |
|--------|------|---------------------|
| POST | `/trips/{trip}/status` | throttle `120,1` |
| POST | `/trips/{trip}/events` | throttle `60,1` |
| PUT | `/trips/{trip}/record` | throttle `30,1` |
| POST | `/trips/{trip}/costs` | **`idempotency`**, throttle `60,1` |
| POST | `/trips/{trip}/costs/{tripCost}/receipt` | throttle `30,1` |
| PATCH | `/trip-costs/{tripCost}` | throttle `30,1` |
| DELETE | `/trip-costs/{tripCost}` | throttle `20,1` |
| POST | `/attachments` | throttle `30,1` |
| DELETE | `/attachments/{attachment}` | throttle `30,1` |
| POST | `/attachments/{attachment}/ocr` | throttle `15,1` |

### E. Đọc điều vận (`dispatch-staff-read.php`) — `dispatch.staff`

| Method | Path | Permission / throttle |
|--------|------|------------------------|
| GET | `/vehicles` | |
| GET | `/vehicles/{vehicle}/conflicts` | |
| GET | `/vehicles/{vehicle}/compliance-documents` | |
| GET | `/vehicles/{vehicle}/compliance-audit` | |
| GET | `/drivers` | |
| GET | `/drivers/workload` | |
| GET | `/drivers/{driver}/workload-detail` | |
| GET | `/drivers/{driver}` | |
| GET | `/drivers/{driver}/compliance-documents` | |
| GET | `/drivers/{driver}/compliance-audit` | |
| GET | `/transport-providers` | |
| GET | `/users/for-driver-assignment` | |
| GET | `/users/for-dispatch-form` | |
| GET | `/requests` | |
| GET | `/reports/summary` | |
| GET | `/reference-pricing` | throttle `60,1` |
| GET | `/reference-pricing/revisions` | **`permission:reference_pricing.manage`**, throttle `60,1` |
| GET | `/audit-logs` | throttle `60,1` |
| GET | `/dispatch-form-settings` | |
| GET | `/admin/users` | throttle `60,1` |
| GET | `/admin/users/search` | throttle `60,1` |
| GET | `/admin/users/{user}/roles` | |
| PUT | `/admin/users/{user}/roles` | |
| * | `/admin/roles` | `apiResource` except create/edit |
| * | `/admin/permissions` | `apiResource` except create/edit |
| * | `/admin/feature-toggles` | `apiResource` except create/edit |
| GET | `/admin/dispatch-settings` | |
| PUT | `/admin/dispatch-settings` | |

### F. Ghi điều vận (`dispatch-staff-mutate.php`) — `dispatch.staff` + `LogApiActivity`

| Method | Path | Ghi chú |
|--------|------|---------|
| POST | `/v1/users/roles/bulk-update` | **`permission:any,system.user_roles.manage`**, throttle `60,1` |
| PATCH | `/reference-pricing/passenger-fares/{passengerFareRate}` | `permission:reference_pricing.manage` |
| PATCH | `/reference-pricing/cargo-fares/{cargoFareRate}` | idem |
| PATCH | `/reference-pricing/notes/{pricingNote}` | idem |
| POST | `/dispatch-requests` | **`idempotency`**, throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/paper-received` | throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/paper-revert` | throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/decision` | **`idempotency`**, throttle `120,1` |
| POST | `/requests/bulk-delete` | |
| POST | `/requests/bulk-restore` | |
| POST | `/requests/bulk-force-delete` | |
| POST | `/trips/{trip}/assign` | **`idempotency`**, throttle `60,1` |
| POST | `/trips/{trip}/reschedule` | throttle `60,1` |
| PATCH | `/trips/{trip}/passenger-list` | throttle `30,1` |
| PATCH | `/trips/{trip}/passengers/{passenger}/checkin` | throttle `60,1` |
| DELETE | `/trips/{trip}/passengers/{passenger}/checkin` | throttle `60,1` |
| POST | `/trips/{trip}/duplicate` | throttle `10,1` |
| POST | `/trip-costs/{tripCost}/decision` | **`idempotency`**, throttle `120,1` |
| PATCH | `/trip-costs/{tripCost}/override` | throttle `10,1` |
| POST | `/cargo-shipments` | throttle `20,1` |
| POST | `/cargo-shipments/{cargoShipment}/status` | throttle `60,1` |
| POST | `/cargo-shipments/{cargoShipment}/pod` | throttle `20,1` |
| POST | `/routes` | throttle `10,1` |
| POST | `/routes/{route}/versions` | throttle `10,1` |
| POST | `/routes/{route}/enroll-students` | throttle `10,1` |
| POST | `/route-versions/{routeVersion}/decision` | throttle `10,1` |
| POST | `/route-versions/{routeVersion}/generate-trip` | throttle `10,1` |
| POST | `/drivers` | throttle `30,1` |
| POST | `/drivers/from-user` | throttle `30,1` |
| POST | `/drivers/bulk-delete` | throttle `30,1` |
| POST | `/drivers/bulk-force-delete` | throttle `30,1` |
| PATCH | `/drivers/{driver}` | throttle `30,1` |
| DELETE | `/drivers/{driver}` | throttle `30,1` |
| POST | `/drivers/{id}/restore` | throttle `30,1` |
| DELETE | `/drivers/{id}/force` | throttle `30,1` |
| POST | `/drivers/{driver}/compliance-documents` | throttle `30,1` |
| PATCH | `/drivers/{driver}/compliance-documents/{complianceDocument}` | throttle `30,1` |
| DELETE | `/drivers/{driver}/compliance-documents/{complianceDocument}` | throttle `30,1` |
| POST | `/vehicles` | throttle `30,1` |
| POST | `/vehicles/bulk-delete` | throttle `30,1` |
| POST | `/vehicles/bulk-force-delete` | throttle `30,1` |
| PATCH | `/vehicles/{vehicle}` | throttle `30,1` |
| DELETE | `/vehicles/{vehicle}` | throttle `30,1` |
| POST | `/vehicles/{id}/restore` | throttle `30,1` |
| DELETE | `/vehicles/{id}/force` | throttle `30,1` |
| POST | `/vehicles/{vehicle}/compliance-documents` | throttle `30,1` |
| PATCH | `/vehicles/{vehicle}/compliance-documents/{complianceDocument}` | throttle `30,1` |
| DELETE | `/vehicles/{vehicle}/compliance-documents/{complianceDocument}` | throttle `30,1` |
| POST | `/transport-providers` | throttle `30,1` |
| POST | `/transport-providers/bulk-delete` | throttle `30,1` |
| POST | `/transport-providers/bulk-force-delete` | throttle `30,1` |
| PATCH | `/transport-providers/{transportProvider}` | throttle `30,1` |
| DELETE | `/transport-providers/{transportProvider}` | throttle `30,1` |
| POST | `/transport-providers/{id}/restore` | throttle `30,1` |
| DELETE | `/transport-providers/{id}/force` | throttle `30,1` |

> Chi tiết validation/request body: mở từng `FormRequest` trong `app/Http/Requests/Api/...` tương ứng controller action.

---

## Internal / helper

- **Sanctum**: `GET /api/user` là canonical “me”.
- **CMS sync**: Console command `SyncUsersFromCmsCommand` — không phải REST public.

---

## Third-party (OAuth web)

Các route trong `routes/web.php` (không có prefix `/api`):

| Method | Path |
|--------|------|
| GET | `/auth/google` |
| GET | `/auth/google/callback` |
| GET | `/login` (view welcome) |
| GET | `/sw.js` (service worker file từ build) |
| GET | `/{any?}` | SPA shell |

---

## Implementation chưa gắn route

> **`App\Http\Controllers\Api\Payments\ReconciliationController`** có đầy đủ action (periods index/show, payments index/show, generate, lock, execute…) nhưng **không được `Route::` khai báo** trong `routes/api/spa/*.php` hay `routes/api.php` tại thời điểm khảo sát repo.

Để expose API thanh toán/đối soát cần **thêm route** (khuyến nghị trong `dispatch-staff-read.php` / `dispatch-staff-mutate.php`) + middleware permission `payment.reconcile`, `payment.execute`.

---

## Liên quan

- [PERMISSION_AND_ROLE.md](./PERMISSION_AND_ROLE.md)
- [FEATURES_AND_MODULES.md](./FEATURES_AND_MODULES.md)
