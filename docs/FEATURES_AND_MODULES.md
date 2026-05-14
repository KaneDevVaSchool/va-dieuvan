# FEATURES_AND_MODULES — VA Điều Vận

## 📑 Mục lục

- [Phân loại module](#phân-loại-module)
- [Core modules](#core-modules)
- [Business modules](#business-modules)
- [Operational modules](#operational-modules)
- [System modules](#system-modules)
- [Shared / cross-cutting](#shared--cross-cutting)

---

## Phân loại module

| Loại | Mô tả |
|------|--------|
| **Core** | Đăng nhập, phiên, hồ sơ, shell SPA |
| **Business** | Luồng điều vận: yêu cầu → chuyến → chi phí → hàng → tuyến → thanh toán |
| **Operational** | Master data xe/TX/NCC, workload, compliance |
| **System** | RBAC, feature toggle, audit, settings, push |

Chi tiết API: [API_OVERVIEW.md](./API_OVERVIEW.md).  
Quyền: [PERMISSION_AND_ROLE.md](./PERMISSION_AND_ROLE.md).

---

## Core modules

### MODULE: AUTHENTICATION & SESSION

**Mục tiêu:** Cho phép user vào SPA với token an toàn và kiểm tra quyền truy cập app.

**Chức năng**

- Đăng nhập email/password → Sanctum PAT (`POST /api/login`)
- Đăng xuất revoke token (`POST /api/logout`)
- Đăng nhập Google OAuth (redirect web `/auth/google`)

**Roles:** Mọi role được phép vào app phải pass `canAccessDispatchWebApp()` hoặc `canAccessDriverWebApp()` (logic `User` model).

**Flow:** Login → nhận `token` + `permissions` + `feature_toggles` → SPA lưu và gắn header.

**API chính:** `AuthController`, routes `routes/api.php`.

**DB:** `users`, `personal_access_tokens`.

**Queue/Event:** Không bắt buộc.

**Validation:** `LoginRequest`.

**Permissions:** Không áp middleware permission riêng trên login; kiểm tra role/access trong controller.

---

### MODULE: USER PROFILE

**Mục tiêu:** Đồng bộ thông tin hiển thị và liên hệ.

**Chức năng**

- Xem profile (`GET /api/user`)
- Cập nhật profile (`PATCH /api/user`)

**Roles:** Mọi user trong nhóm `dispatch.web`.

**API:** `UserProfileController`.

**DB:** `users`.

---

## Business modules

### MODULE: DISPATCH REQUESTS (Yêu cầu điều xe)

**Mục tiêu:** Thu thập nhu cầu xe, duyệt/từ chối, quản lý phiếu giấy và mức độ khẩn.

**Chức năng**

- Tạo yêu cầu (staff mutate + idempotency)
- Phê duyệt / quyết định (`decision`)
- Đánh dấu nhận phiếu giấy / revert
- Export PDF (`export-pdf`)
- Đọc chi tiết (`show`)

**Roles:** `internal_user` (tạo/sửa của mình — theo policy/request), `dispatcher`/`admin` duyệt & giấy.

**Flow:** draft/pending → approved/rejected → sinh/trips liên quan (logic service/controller).

**API:** `DispatchRequestController`, `RequestController` (list index / bulk soft-delete restore).

**DB:** `dispatch_requests`.

**Queue/Event:** `NewDispatchRequestNotification` (nếu codebase emit — kiểm tra controller/service khi bổ sung).

**Permissions:** `request.*`, `request.paper.manage`.

---

### MODULE: TRIPS & DISPATCH BOARD

**Mục tiêu:** Phân công tài nguyên và điều phối chuyến sau duyệt.

**Chức năng**

- List/filter/stats chuyến (`TripController@index`, `@stats`)
- Chi tiết chuyến
- **Assign** xe/tài xế/NCC + optimistic lock (`DispatchingService`)
- **Reschedule** khung giờ giữ duration
- Duplicate trip
- Passenger list / check-in named passengers

**Roles:** Dispatcher/admin assign; tài xế xem chuyến của mình.

**API:** `TripController`, đọc chung; mutate trong `dispatch-staff-mutate`.

**DB:** `trips`, `trip_passengers`.

**Queue/Event:** `TripAssignedNotification` → queue → Web Push listener.

**Permissions:** `trip.assign`, `trip.view_all`, …

---

### MODULE: TRIP OPERATIONS (TÀI XẾ)

**Mục tiêu:** Thực thi chuyến trên field — trạng thái, sự kiện, nhật ký.

**Chức năng**

- Đổi `status` chuyến
- Thêm `trip_events`
- Upsert `trip_records` (odometer, notes)

**Roles:** `driver` (+ dispatcher nếu có quyền status/event trong FormRequest).

**API:** `TripOpsController` (`driver-mutate.php`).

**DB:** `trips`, `trip_events`, `trip_records`.

**Permissions:** `trip.update_status`, `trip.record.create`, `trip.event.create`.

---

### MODULE: TRIP COSTS

**Mục tiêu:** Ghi nhận và duyệt chi phí thực tế.

**Chức năng**

- Tài xế tạo/sửa/xóa chi phí (draft/submitted)
- Upload receipt
- Staff reconcile: decision confirmed/rejected, override (theo route)

**API:** `TripCostController`.

**DB:** `trip_costs`, `attachments`.

**Queue:** Không riêng — có thể mở rộng notify sau.

**Permissions:** `trip.cost.view`, `trip.cost.reconcile`, `data.override_confirmed`.

---

### MODULE: CARGO SHIPMENTS

**Mục tiêu:** Theo dõi hàng hóa và SLA.

**Chức năng**

- CRUD/list/timeline (đọc chung; ghi staff)
- Đổi status, upload POD

**API:** `CargoController`.

**DB:** `cargo_shipments`, `attachments`.

**Background:** `cargo:sla-check` → audit + `CargoSlaBreachedNotification`.

**Permissions:** `cargo.manage`.

---

### MODULE: D2D ROUTES & STUDENTS

**Mục tiêu:** Quản lý tuyến cửa-cửa, phiên bản, học sinh, sinh chuyến.

**Chức năng**

- Routes CRUD/versioning/enroll/generate trip (theo `dispatch-staff-mutate`)

**API:** `D2D\RouteController`.

**DB:** `routes`, `route_versions`, `route_stops`, `students`, `route_students`, `route_schedules`, `route_runs`.

**Permissions:** `route.manage`, `student.manage`.

---

### MODULE: RECONCILIATION & PAYMENTS

**Mục tiêu:** Kỳ đối soát và thanh toán theo chuyến.

**Chức năng**

- Logic có trong `App\Http\Controllers\Api\Payments\ReconciliationController` + các `FormRequest` trong `app/Http/Requests/Api/Payments/`.

> **Gap:** Chưa có route HTTP đăng ký tới controller này trong `routes/` — xem [API_OVERVIEW.md](./API_OVERVIEW.md) mục *Implementation chưa gắn route*.

**DB:** `reconciliation_periods`, `payments`, `trips.payment_status`.

**Permissions:** `payment.reconcile`, `payment.execute`.

---

### MODULE: REFERENCE PRICING

**Mục tiêu:** Bảng giá tham chiếu hành khách/hàng + ghi revision.

**Chức năng**

- Đọc pricing; staff PATCH với permission; revisions endpoint.

**API:** `ReferencePricingController`.

**DB:** `passenger_fare_rates`, `cargo_fare_rates`, `pricing_notes`, `reference_pricing_revisions`.

**Permissions:** `reference_pricing.manage`.

---

### MODULE: REPORTS

**Mục tiêu:** Báo cáo tổng hợp vận tải.

**API:** `ReportController` (`/api/reports/summary`).

**Permissions:** `report.view`, `report.export`.

---

## Operational modules

### MODULE: OPERATIONAL RESOURCES

**Mục tiêu:** CRUD master **drivers**, **vehicles**, **transport providers**.

**API:** `OperationalResourceController` + bulk restore/force-delete endpoints.

**DB:** `drivers`, `vehicles`, `transport_providers`.

**Permissions:** `resource.driver.manage`, `resource.vehicle.manage`, `resource.provider.manage`.

---

### MODULE: DRIVER WORKLOAD

**Mục tiêu:** Hỗ trợ dispatcher xếp lịch — tải công việc tài xế.

**API:** `DriverWorkloadController` (`workload`, `workload-detail`).

**Service:** `DriverWorkloadService`.

---

### MODULE: COMPLIANCE DOCUMENTS

**Mục tiêu:** Lưu giấy tờ hết hạn cho tài xế và xe.

**API:** `DriverComplianceDocumentController`, `VehicleComplianceDocumentController`.

**DB:** `driver_compliance_documents`, `vehicle_compliance_documents`.

---

### MODULE: USER SEARCH / ASSIGNMENT HELPERS

**Mục tiêu:** Autocomplete user cho form điều xe và gán tài xế.

**API:** `UserSearchForDispatchFormController`, `UserSearchForDriverAssignmentController`.

---

## System modules

### MODULE: RBAC ADMIN UI API

**Chức năng**

- Users list/search/roles
- Roles & permissions CRUD (apiResource)
- Bulk update roles (`BulkUserRolesUpdateController`)

**Permissions:** `system.roles.manage`, `system.permissions.manage`, `system.user_roles.manage`, middleware `permission:any,system.user_roles.manage` trên bulk route.

---

### MODULE: FEATURE TOGGLES

**API:** `FeatureToggleController`.

**DB:** `feature_toggles`.

**Middleware:** `feature` alias — route/feature keys đồng bộ FE.

**Permission:** `system.feature_toggles.manage`.

---

### MODULE: DISPATCH SETTINGS

**API:** `DispatchSettingController` — wizard settings + admin PUT.

**DB:** `dispatch_settings`.

**Permission:** `dispatch.settings.manage` (admin routes).

---

### MODULE: NOTIFICATIONS INBOX & PUSH

**Chức năng**

- Inbox đọc/đánh dấu đọc
- Đăng ký Web Push + VAPID public key

**API:** `InboxController`, `PushSubscriptionController`.

**DB:** `notifications`, `push_subscriptions`.

---

### MODULE: AUDIT LOGS

**API:** `AuditLogController` index (staff, throttle).

**DB:** `audit_logs`.

**Permission:** `audit_log.view`.

---

### MODULE: CLIENT TELEMETRY

**API:** `POST /api/telemetry/frontend` (public throttle).

**Mục đích:** Thu metrics lỗi/perf phía client — không thay cho server logs.

---

### MODULE: NAV BADGES

**API:** `GET /api/nav/badges` — số badge menu (unread, pending…).

---

## Shared / cross-cutting

| Concern | Implementation |
|---------|----------------|
| Idempotency | Middleware `idempotency` + `idempotent_requests` |
| Rate limit | `throttle` trên route groups |
| Activity log | `LogApiActivity` trên mutate |
| Offline/PWA | Vue `core/offline`, service worker |
| PDF | DomPDF + Blade |

---

## Liên quan

- [API_OVERVIEW.md](./API_OVERVIEW.md)
- [DATABASE_SPECIFICATION.md](./DATABASE_SPECIFICATION.md)
