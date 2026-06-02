# API_OVERVIEW — VA Điều Vận

## 📑 Mục lục

- [Quy ước chung](#quy-ước-chung)
- [Authentication](#authentication)
- [Telemetry](#telemetry)
- [Sanctum — profile & Portal](#sanctum--profile--portal)
- [Nhóm route SPA (dispatch.web)](#nhóm-route-spa-dispatchweb)
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
2. `auth:sanctum` — các endpoint SPA và Portal.
3. `dispatch.web` — user được vào app điều vận/tài xế (nhóm `routes/api/spa/*`).
4. `dispatch.staff` — superadmin, admin, dispatcher, **department_head** (không phải tài khoản chỉ driver).
5. `driver.spa` — chỉ tài xế.
6. `LogApiActivity` — các nhóm **mutate** ghi log hoạt động.

Source: [routes/api.php](../routes/api.php), [routes/api/spa/*.php](../routes/api/spa/).

> **Ngoại lệ:** nhóm **`/api/portal/*`** đăng ký trực tiếp trong `routes/api.php` (chỉ `auth:sanctum`, không qua `dispatch.web`) — xem mục [Sanctum — profile & Portal](#sanctum--profile--portal). Chi tiết UX Portal: [PORTAL_NEW_STRUCTURE.md](./PORTAL_NEW_STRUCTURE.md).

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

## Sanctum — profile & Portal

Yêu cầu **`auth:sanctum`** — **không** qua `dispatch.web`. Dùng cho portal user (`internal_user`) và profile chung.

### Profile

| Method | Path | Ghi chú |
|--------|------|---------|
| GET | `/user` | Profile canonical — throttle `120,1` |

### Portal — đọc (`routes/api.php`, throttle `120,1`)

| Method | Path | Ghi chú |
|--------|------|---------|
| GET | `/portal/dispatch-requests/summary` | |
| GET | `/portal/dispatch-requests` | |
| GET | `/portal/dispatch-requests/{dispatchRequest}` | |
| GET | `/portal/dispatch-requests/{dispatchRequest}/export-pdf` | throttle `30,1` |
| GET | `/portal/dispatch-requests/{dispatchRequest}/attachments/{attachment}/download` | `{attachment}` numeric, throttle `60,1` |
| GET | `/portal/notifications` | |
| POST | `/portal/notifications/read-all` | |
| POST | `/portal/notifications/{notification}/read` | UUID |
| GET | `/portal/form-templates` | Biểu mẫu đã lưu |
| GET | `/portal/form-templates/{portalFormTemplate}` | |
| GET | `/portal/users/for-dispatch-form` | Autocomplete người đi |

### Portal — ghi (`LogApiActivity`, throttle `180,1`)

| Method | Path | Ghi chú |
|--------|------|---------|
| POST | `/portal/dispatch-requests` | **`idempotency`** |
| POST | `/portal/dispatch-request-templates` | **`idempotency`**, throttle `20,1` |
| PATCH | `/portal/dispatch-request-templates/{dispatchRequestTemplate}` | throttle `20,1` |
| PATCH | `/portal/dispatch-request-templates/{dispatchRequestTemplate}/plan-label` | throttle `30,1` |
| POST | `/portal/dispatch-requests/{dispatchRequest}/signed-paper` | throttle `30,1` |
| POST | `/portal/dispatch-requests/{dispatchRequest}/proposal-basis` | throttle `30,1` |
| PATCH | `/portal/dispatch-requests/{dispatchRequest}/recurring-instance` | throttle `60,1` |
| POST | `/portal/dispatch-requests/{dispatchRequest}/submit-recurring` | throttle `30,1` |
| POST | `/portal/form-templates` | throttle `30,1` |
| PATCH | `/portal/form-templates/{portalFormTemplate}` | throttle `30,1` |
| DELETE | `/portal/form-templates/{portalFormTemplate}` | throttle `30,1` |

---

## Nhóm route SPA (dispatch.web)

Tất cả endpoint dưới đây yêu cầu **`auth:sanctum`** + **`dispatch.web`** trừ khi ghi chú khác.

### A. Đọc chung (`common-read.php`)

| Method | Path | Ghi chú |
|--------|------|---------|
| GET | `/push/vapid-public-key` | Web Push |
| POST | `/push/subscriptions` | throttle `20,1` |
| DELETE | `/push/subscriptions` | throttle `20,1` |
| GET | `/notifications/inbox` | |
| POST | `/notifications/read-all` | |
| POST | `/notifications/{notification}/read` | UUID |
| GET | `/dispatch-requests/{dispatchRequest}` | |
| GET | `/dispatch-requests/{dispatchRequest}/audit-logs` | throttle `60,1` |
| GET | `/dispatch-requests/{dispatchRequest}/available-dept-heads` | **`permission:request.fill_price`**, throttle `60,1` |
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

| Method | Path | Ghi chú |
|--------|------|---------|
| PATCH | `/user` | |
| POST | `/dispatch-requests` | **`idempotency`**, `permission:request.create`, throttle `20,1` |
| POST | `/dispatch-request-templates` | **`idempotency`**, throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/clone` | **`idempotency`**, `permission:request.create`, throttle `20,1` |
| PATCH | `/dispatch-requests/{dispatchRequest}/wizard` | **`idempotency`**, `permission:request.create`, throttle `20,1` |
| PATCH | `/dispatch-requests/{dispatchRequest}/passenger-count` | `permission:any,request.update_own,trip.view_all`, throttle `60,1` |
| POST | `/dispatch-requests/{dispatchRequest}/submit-student-count` | `permission:any,request.update_own,trip.view_all`, throttle `30,1` |

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
| GET | `/vehicles/{vehicle}` | |
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
| GET | `/dept/summary` | **`permission:request.approve_dept`** |
| GET | `/reports/summary` | |
| GET | `/reference-pricing` | throttle `60,1` |
| GET | `/reference-pricing/suggest` | throttle `60,1` |
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
| GET | `/campuses` | **`permission:p2p_policy.view`** |
| GET | `/academic-terms` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/terms` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/fixed-holidays` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/terms/{p2pPolicyTerm}` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/terms/{p2pPolicyTerm}/readiness` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/routes` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/routes/{policyRoute}` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/students` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/trip-slots` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/students/export` | **`permission:p2p_policy.import_export`** |
| GET | `/p2p-policy/students/import-template` | **`permission:p2p_policy.import_export`** |
| GET | `/p2p-policy/generation-runs` | **`permission:p2p_policy.view`** |
| GET | `/p2p-policy/generation-runs/{policyGenerationRun}` | **`permission:p2p_policy.view`** |

### F. Ghi điều vận (`dispatch-staff-mutate.php`) — `dispatch.staff` + `LogApiActivity`

| Method | Path | Ghi chú |
|--------|------|---------|
| POST | `/v1/users/roles/bulk-update` | **`permission:any,system.user_roles.manage`**, throttle `60,1` |
| PATCH | `/reference-pricing/passenger-fares/{passengerFareRate}` | `permission:reference_pricing.manage` |
| PATCH | `/reference-pricing/cargo-fares/{cargoFareRate}` | idem |
| PATCH | `/reference-pricing/notes/{pricingNote}` | idem |
| POST | `/dispatch-requests/{dispatchRequest}/paper-received` | throttle `20,1` |
| PATCH | `/dispatch-requests/{dispatchRequest}/fill-price` | throttle `20,1` |
| PATCH | `/dispatch-requests/{dispatchRequest}/pricing-hints` | **`permission:request.fill_price`**, throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/paper-revert` | throttle `20,1` |
| POST | `/dispatch-requests/{dispatchRequest}/decision` | **`idempotency`**, throttle `120,1` |
| POST | `/dispatch-requests/{dispatchRequest}/dept-decision` | **`idempotency`**, **`permission:request.approve_dept`**, throttle `120,1` |
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
| POST | `/campuses` | **`permission:p2p_policy.manage`** |
| PATCH | `/campuses/{campus}` | **`permission:p2p_policy.manage`** |
| POST | `/academic-terms` | **`permission:p2p_policy.manage`** |
| PATCH | `/academic-terms/{academicTerm}` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/terms` | **`permission:p2p_policy.manage`** |
| PATCH | `/p2p-policy/terms/{p2pPolicyTerm}` | **`permission:p2p_policy.manage`** |
| PUT | `/p2p-policy/terms/{p2pPolicyTerm}/calendar` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/terms/{p2pPolicyTerm}/activate` | **`permission:p2p_policy.activate`**, **`idempotency`**, throttle `30,1` |
| POST | `/p2p-policy/routes` | **`permission:p2p_policy.manage`** |
| PATCH | `/p2p-policy/routes/{policyRoute}` | **`permission:p2p_policy.manage`** |
| PATCH | `/p2p-policy/routes/{policyRoute}/assignment` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/students/bulk-delete` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/students/bulk-assign` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/students` | **`permission:p2p_policy.manage`** |
| PATCH | `/p2p-policy/students/{policyStudent}` | **`permission:p2p_policy.manage`** |
| DELETE | `/p2p-policy/students/{policyStudent}` | **`permission:p2p_policy.manage`** |
| POST | `/p2p-policy/students/import` | **`permission:p2p_policy.import_export`**, throttle `10,1` |
| POST | `/p2p-policy/students/import/preview` | **`permission:p2p_policy.import_export`**, throttle `20,1` |
| POST | `/p2p-policy/students/import/commit` | **`permission:p2p_policy.import_export`**, throttle `10,1` |

> Chi tiết validation/request body: mở từng `FormRequest` trong `app/Http/Requests/Api/...` tương ứng controller action.

---

## Internal / helper

- **Sanctum**: `GET /api/user` là canonical “me” (ngoài `dispatch.web` — portal user vẫn gọi được).
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
- [PORTAL_NEW_STRUCTURE.md](./PORTAL_NEW_STRUCTURE.md)
