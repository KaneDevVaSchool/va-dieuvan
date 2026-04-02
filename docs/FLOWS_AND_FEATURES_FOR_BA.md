<!-- markdownlint-disable MD022 MD032 MD012 -->

# Phần mềm điều vận – Luồng nghiệp vụ & tính năng (Checklist cho BA)

Tài liệu này dùng để BA đối chiếu với BRD và kiểm tra xem hệ thống còn thiếu gì.  
Phạm vi mô tả: **những phần đã có trong codebase hiện tại** + **các điểm gap/cần hoàn thiện**.

---

## 1) Tóm tắt kiến trúc dữ liệu (Core Concept)

- **Request (`dispatch_requests`)**: nơi chuẩn hoá nhu cầu phát sinh (portal/Zalo/phiếu giấy).
- **Trip (`trips`)**: thực thể trung tâm vận hành (phân công, trạng thái thực hiện, nhật ký, chi phí, thanh toán).
- **Cost (`trip_costs`)**: mọi chi phí phải gắn với Trip, có luồng submit → kế toán confirm/reject → đối soát/thanh toán.
- **Payment (`payments`) + Reconciliation (`reconciliation_periods`)**: đối soát theo kỳ và ghi nhận thanh toán.
- **Audit (`audit_logs`)**: log mọi hành động quan trọng.
- **Attachment (`attachments`)**: lưu file chứng từ/POD/phiếu đề xuất scan/PDF… (morph).

---

## 2) Roles & RBAC

### Roles
- **Admin**
- **Dispatcher**
- **Tài xế**
- **Kế toán**
- **User nội bộ**

### RBAC mechanism
- Auth: `auth:sanctum`
- Authorization:
  - `role:<roleName>`
  - `permission:<perm>` hoặc `permission:any,...` / `permission:all,...`

### Permissions (đã seed)
Xem `database/seeders/RbacSeeder.php`. Nhóm chính:
- **Request**: `request.create`, `request.approve`, `request.paper.manage`
- **Trip**: `trip.assign`, `trip.update_status`, `trip.record.create`, `trip.event.create`
- **Cost**: `trip.cost.reconcile`, `data.override_confirmed`
- **Payment**: `payment.reconcile`, `payment.execute`
- **Door-to-door**: `route.manage`, `student.manage`
- **Cargo**: `cargo.manage`
- **Attachment**: `attachment.upload`
- **Report**: `report.view`, `report.export`

---

## 3) Luồng Request (Tạo yêu cầu) – portal/Zalo/phiếu giấy

### 3.1 Tạo yêu cầu (User nội bộ hoặc Dispatcher tạo thay)
**API**: `POST /api/dispatch-requests` (permission: `request.create`)

Input chính:
- `trip_type`: `door_to_door | point_to_point | business | cargo`
- `origin`, `destination`
- `depart_at`, `arrive_by?`
- `passenger_count?`, `notes?`
- `source_channel?`: `portal | zalo | paper` (mặc định `portal`)
- `is_urgent?`: boolean (lệnh gấp)
- `requester_id?`: *Dispatcher/Admin* có thể tạo thay cho người đề xuất khi nhận qua Zalo

Business rules:
- **BR-001**: yêu cầu phải tạo trước ≥ 2 tiếng  
  - **Ngoại lệ**: nếu `is_urgent=true` thì cho phép vượt rule (để “điều xe trước, nhận phiếu sau”).

Trạng thái sau khi tạo:
- `dispatch_requests.status = pending`
- `dispatch_requests.paper_status = pending`

Audit:
- `request.create`

### 3.2 Nhận phiếu giấy (nhận sau – lưu online)
**API**: `POST /api/dispatch-requests/{id}/paper-received` (permission: `request.paper.manage`)

Tác dụng:
- set `paper_status=received`
- set `paper_received_at` (mặc định now nếu không truyền)
- set `paper_reference` (optional)

Audit:
- `request.paper_received`

### 3.3 Upload scan/PDF phiếu đề xuất để lưu online

**API**: `POST /api/attachments` (permission: `attachment.upload`)  
Multipart:
- `attachable_type=dispatch_request`
- `attachable_id=<dispatchRequestId>`
- `kind=proposal_form` (gợi ý)
- `file=<scan/pdf/image>`

Kết quả:
- tạo record `attachments`
- trả về `url` tải file

Audit:
- `attachment.upload`

---

## 4) Luồng phê duyệt Request → tạo Trip

**API**: `POST /api/dispatch-requests/{id}/decision` (permission: `request.approve`)

Body:
- `{ "decision": "approve" }`
- hoặc `{ "decision": "reject", "reason": "..." }`

Kết quả:
- Approve:
  - `dispatch_requests.status=approved`
  - tạo `trips` với `status=approved`
- Reject:
  - `dispatch_requests.status=rejected`
  - lưu `rejection_reason`

Audit:
- `request.approve` / `request.reject`

---

## 5) Luồng điều phối (Assign resources) + chống trùng lịch (BR-002)

**API**: `POST /api/trips/{trip}/assign` (permission: `trip.assign`)

Input:
- `lock_version` (bắt buộc)
- `vehicle_id?`, `driver_id?`, `transport_provider_id?`
- `external_vehicle_ref?`, `external_driver_ref?`

Business rules:
- **BR-002**: chống trùng lịch xe/tài xế
  - Interval overlap: `a.start < b.end AND b.start < a.end`
  - Nếu `arrive_by` null: planned end mặc định `depart_at + 2h`
- **Optimistic lock**:
  - mismatch `lock_version` → `409 Conflict`

Kết quả:
- `trips.status=assigned`
- tăng `lock_version`

Audit:
- `trip.assign`

---

## 6) Luồng vận hành Trip (Tracking)

### 6.1 Update status
**API**: `POST /api/trips/{trip}/status` (permission: `trip.update_status`)

Status supported:
- `driver_confirmed`, `in_progress`, `completed`, `incident`, `cancelled`

Side effects:
- khi `in_progress`: set `started_at` (nếu chưa có)
- khi `completed/cancelled`: set `completed_at` (nếu chưa có)
- tạo `trip_events` (type `status_change`)

Audit:
- `trip.status_change`

### 6.2 Add event (note/incident/…)
**API**: `POST /api/trips/{trip}/events` (permission: `trip.event.create`)

Tạo `trip_events` tuỳ `type`.

Audit:
- `trip.event.create`

---

## 7) Luồng chi phí (Cost)

### 7.1 Submit cost
**API**: `POST /api/trips/{trip}/costs`  
Permission: `any(trip.record.create, trip.update_status)`

Tạo `trip_costs.status=submitted`.

Audit:
- `cost.submit`

### 7.2 Kế toán confirm/reject
**API**: `POST /api/trip-costs/{tripCost}/decision` (permission: `trip.cost.reconcile`)

Audit:
- `cost.confirm` / `cost.reject`

### 7.3 Admin override
**API**: `PATCH /api/trip-costs/{tripCost}/override` (permission: `data.override_confirmed`)

Audit:
- `cost.override`

### 7.4 Upload chứng từ chi phí
**API**: `POST /api/attachments`  
`attachable_type=trip_cost`, `kind=receipt`

---

## 8) Đối soát theo kỳ & Thanh toán

### 8.1 Tạo kỳ đối soát
**API**: `POST /api/reconciliation-periods` (permission: `payment.reconcile`)

### 8.2 Lock kỳ
**API**: `POST /api/reconciliation-periods/{id}/lock` (permission: `payment.reconcile`)

### 8.3 Generate payments cho Trip
**API**: `POST /api/reconciliation-periods/{id}/generate-payments` (permission: `payment.reconcile`)

Logic:
- amount = SUM confirmed costs theo trip
- tạo/ cập nhật `payments` theo `trip_id`

Audit:
- `payment.generate`

### 8.4 Execute payment
**API**: `POST /api/payments/{payment}/execute` (permission: `payment.execute`)

Side effects:
- update `payments.status`
- sync `trips.payment_status` + `trips.paid_at` khi paid

Audit:
- `payment.execute`
- `trip.payment_sync`

---

## 9) Cargo (Hàng hóa) – SLA 3 giờ + POD

### 9.1 Tạo shipment
**API**: `POST /api/cargo-shipments` (permission: `cargo.manage`)

Logic:
- mặc định SLA 3h (`sla_due_at = now + 3h`) hoặc custom `sla_hours`

Audit:
- `cargo.create`

### 9.2 Update trạng thái shipment
**API**: `POST /api/cargo-shipments/{id}/status` (permission: `cargo.manage`)

Return:
- `sla_breached` boolean (quá SLA và chưa delivered)

Audit:
- `cargo.status_change`

### 9.3 Upload POD
**API**: `POST /api/cargo-shipments/{id}/pod` (permission: `cargo.manage`)

Kết quả:
- tạo `attachments` kind=`pod` gắn vào cargo shipment

Audit:
- `cargo.pod.upload`

### 9.4 SLA monitoring (scheduler)
Command:
- `php artisan cargo:sla-check` (đã schedule mỗi 5 phút)

Hành vi:
- quét shipment breach SLA
- log audit `cargo.sla_breached`
- gửi database notification tới `dispatcher/admin`

---

## 10) Door-to-door (Tuyến) – versioning + schedule + generate trip

### 10.1 Tạo route
**API**: `POST /api/routes` (permission: `route.manage`)

### 10.2 Tạo route version (stops + schedules)
**API**: `POST /api/routes/{route}/versions` (permission: `route.manage`)

### 10.3 Approve / archive version
**API**: `POST /api/route-versions/{routeVersion}/decision` (permission: `route.manage`)

### 10.4 Enroll học sinh vào route
**API**: `POST /api/routes/{route}/enroll-students` (permission: `student.manage`)

### 10.5 Generate trip cho ngày chạy
**API**: `POST /api/route-versions/{routeVersion}/generate-trip` (permission: `route.manage`)

Kết quả:
- tạo `trips`
- tạo/ cập nhật `route_runs` map version+date → trip

Audit:
- `route.run.generate_trip`

---

## 11) Báo cáo

**API**: `GET /api/reports/summary` (permission: `report.view`)

Hiện có:
- `trips_by_status`
- `confirmed_costs_by_type`
- `confirmed_costs_by_provider`
- `cargo_sla_breaches`

---

## 12) Danh sách endpoints (tham khảo nhanh)

Lấy từ `php artisan route:list` (tính tới hiện tại):
- `/api/dispatch-requests*` (create/decision/paper-received)
- `/api/trips*` (assign/status/events/costs)
- `/api/trip-costs*` (decision/override)
- `/api/reconciliation-periods*` (create/lock/generate-payments)
- `/api/payments/{payment}/execute`
- `/api/cargo-shipments*` (create/status/pod)
- `/api/routes*` (create/version/enroll/generate-trip)
- `/api/students`
- `/api/attachments`
- `/api/reports/summary`

---

## 13) Checklist “BA kiểm tra còn thiếu gì?”

### A) Request
- [ ] UI/form cho nguồn `portal/zalo/paper`
- [ ] Quy định ai được set `requester_id` khi nhận từ Zalo (đã có rule: dispatcher/admin)
- [ ] Quy định “lệnh gấp” có cần workflow phê duyệt riêng không?
- [ ] Cần trạng thái `paper_status=digitally_signed` (để tích hợp sau) hay bỏ khỏi UI?

### B) Trip lifecycle
- [ ] Trạng thái đầy đủ theo BRD: có cần `paid` như 1 status riêng trong trip.status hay chỉ `payment_status`?
- [ ] Quy tắc chuyển trạng thái (state machine) có cần khóa chặt hơn?

### C) Door-to-door
- [ ] Import danh sách học sinh từ Excel/CRM (chưa có)
- [ ] Generate trip hàng loạt theo schedule tuần/tháng (chưa có)
- [ ] Điểm danh theo tuyến (chưa có)
- [ ] Version control UI cho route version (đã có data + API, thiếu UI)

### D) Cargo
- [ ] SLA escalation: cảnh báo kênh nào (notification/email/Zalo OA)? (hiện: database notification)
- [ ] Tracking code generation (chưa có auto)

### E) Cost/Payment
- [ ] Khóa sửa dữ liệu sau confirm/paid (hiện: chưa enforce “lock rules” ở API layer)
- [ ] Xuất biên bản/Excel/PDF (chưa có)

### F) Security/Compliance
- [ ] Phân quyền theo “phạm vi dữ liệu thuộc mình” (row-level access) (chưa có)
- [ ] Mã hoá dữ liệu nhạy cảm (CCCD, …) (chưa implement app-layer encryption)

---

## 14) Ghi chú scope

- Ký số online / tích hợp eSign: **để phase sau** (đã chuẩn bị `paper_status=digitally_signed`).

