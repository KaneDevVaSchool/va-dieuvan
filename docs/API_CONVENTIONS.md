# API conventions (Laravel REST) – Phần mềm điều vận

## 1) Auth

- API dùng `auth:sanctum`
- Tất cả endpoint nội bộ đặt dưới prefix `/api/*`

## 2) RBAC

### Middleware

- **Role**: `role:<roleName>`
- **Permission**:
  - `permission:request.create`
  - `permission:any,request.create,request.approve`
  - `permission:all,trip.assign,trip.view_all`

### Permission naming

Định dạng: `<module>.<action>[.<scope>]`

Ví dụ:

- `request.create`
- `request.approve`
- `trip.assign`
- `trip.cost.reconcile`
- `data.override_confirmed`

## 3) Response shape

### Thành công

- `200 OK`

```json
{ "data": { } }
```

- `201 Created`

```json
{ "data": { } }
```

### Lỗi

- `401 Unauthorized`: chưa đăng nhập / token invalid
- `403 Forbidden`: không đủ quyền
- `409 Conflict`: optimistic locking / trùng lịch / trạng thái không hợp lệ
- `422 Unprocessable Entity`: validation (BR-001…)

Laravel mặc định trả format validation errors theo `errors`.

## 4) Optimistic locking (BR-002)

Client phải gửi `lock_version` khi thực hiện các hành động có cạnh tranh (ví dụ phân công).

Ví dụ:

`POST /api/trips/{id}/assign`

```json
{
  "lock_version": 3,
  "vehicle_id": 10,
  "driver_id": 5
}
```

Nếu `lock_version` mismatch → `409 Conflict`.

## 5) Interval overlap (chống trùng lịch)

Quy tắc overlap:

\[
 a.start < b.end \ \wedge \ b.start < a.end
\]

Nếu `arrive_by` null, hệ thống dùng planned end mặc định \(depart\_at + 2h\) để kiểm tra.

## 6) Endpoints hiện có (scaffold)

### Dispatch request

- `POST /api/dispatch-requests` (permission: `request.create`)
  - BR-001: `depart_at >= now + 2h`

- `POST /api/dispatch-requests/{dispatchRequest}/decision` (permission: `request.approve`)

Body:

```json
{ "decision": "approve" }
```

hoặc

```json
{ "decision": "reject", "reason": "..." }
```

### Trips

- `POST /api/trips/{trip}/assign` (permission: `trip.assign`)

### Costs

- `POST /api/trips/{trip}/costs` (permission: any of `trip.record.create` OR `trip.update_status`)
- `POST /api/trip-costs/{tripCost}/decision` (permission: `trip.cost.reconcile`)
- `PATCH /api/trip-costs/{tripCost}/override` (permission: `data.override_confirmed`)

## 7) Audit log

Các event hiện đã log:

- `request.create`
- `request.approve` / `request.reject`
- `trip.assign`
- `cost.submit`
- `cost.confirm` / `cost.reject`
- `cost.override`
