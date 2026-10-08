# API Endpoints — VA Điều Vận

**Base URL**: `/api`  
**Auth**: `Authorization: Bearer {sanctum_token}`  
**Content-Type**: `application/json`

---

## Authentication

| Method | Endpoint | Auth | Mô tả |
|--------|----------|------|-------|
| POST | `/api/login` | ❌ | Đăng nhập email/password |
| GET | `/auth/google` | ❌ | Redirect Google OAuth |
| GET | `/auth/google/callback` | ❌ | Google OAuth callback |
| DELETE | `/api/logout` | ✅ | Đăng xuất |

---

## Common (tất cả user đã auth)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/me` | Profile user hiện tại |
| PUT | `/api/me` | Cập nhật profile |
| GET | `/api/nav-badges` | Badge counts cho navigation |
| GET | `/api/notifications` | Inbox thông báo |
| PUT | `/api/notifications/{id}/read` | Đánh dấu đã đọc |
| POST | `/api/push-subscriptions` | Đăng ký push notification |
| DELETE | `/api/push-subscriptions` | Hủy đăng ký push |

---

## Portal (Nhân viên gửi yêu cầu)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/portal/dispatch-requests` | Tạo yêu cầu mới |
| GET | `/api/portal/dispatch-requests` | Danh sách yêu cầu của tôi |
| GET | `/api/portal/dispatch-requests/{id}` | Chi tiết yêu cầu |
| POST | `/api/portal/dispatch-requests/{id}/cancel` | Hủy yêu cầu |
| POST | `/api/portal/dept-head/approve/{id}` | Trưởng đơn vị duyệt |
| POST | `/api/portal/dept-head/reject/{id}` | Trưởng đơn vị từ chối |
| GET | `/api/portal/form-templates` | Templates form |
| GET | `/api/portal/notifications` | Thông báo portal |

---

## Dispatch Staff (Dispatcher/Admin)

### Requests
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/dispatch-requests` | Danh sách yêu cầu |
| GET | `/api/dispatch-requests/{id}` | Chi tiết |
| PUT | `/api/dispatch-requests/{id}` | Cập nhật |
| POST | `/api/dispatch-requests/{id}/approve` | Phê duyệt |
| POST | `/api/dispatch-requests/{id}/reject` | Từ chối |
| POST | `/api/dispatch-requests/{id}/cancel` | Hủy |

### Trips
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/trips` | Danh sách chuyến |
| GET | `/api/trips/stats` | KPI thống kê |
| GET | `/api/trips/{id}` | Chi tiết chuyến |
| POST | `/api/trips/{id}/assign` | Gán xe/tài xế |
| POST | `/api/trips/{id}/reschedule` | Đổi lịch |
| POST | `/api/trips/{id}/passenger-list` | Cập nhật danh sách HK |
| POST | `/api/trips/{id}/passenger-check-in/{key}` | Check-in HK |
| DELETE | `/api/trips/{id}/passenger-check-in/{key}` | Hủy check-in |
| POST | `/api/trips/{id}/duplicate` | Nhân bản |
| POST | `/api/trips/{id}/start` | Bắt đầu chuyến |
| POST | `/api/trips/{id}/complete` | Hoàn thành |

### Costs
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/trip-costs` | Danh sách chi phí |
| POST | `/api/trip-costs` | Tạo chi phí |
| PUT | `/api/trip-costs/{id}` | Cập nhật |
| DELETE | `/api/trip-costs/{id}` | Xóa |
| GET | `/api/trips/{id}/wizard-cost` | Chi phí ước tính |

### Transport Program
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/transport-programs` | Danh sách TP |
| POST | `/api/transport-programs` | Tạo TP |
| GET | `/api/transport-programs/{id}` | Chi tiết |
| PUT | `/api/transport-programs/{id}` | Cập nhật |
| GET | `/api/tp-program-days` | Danh sách ngày |
| GET | `/api/tp-attendance/{dayId}` | Xem điểm danh |
| POST | `/api/tp-attendance/{dayId}/absent` | Đánh vắng |
| POST | `/api/tp-attendance/{dayId}/confirm` | Xác nhận điểm danh |
| GET | `/api/tp-students` | Danh sách học sinh |
| GET | `/api/tp-students/export` | Export Excel |

### Reports
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/reports/summary` | Báo cáo tổng quan |
| GET | `/api/reports/trip-cost` | Báo cáo chi phí chuyến |
| GET | `/api/reports/driver-frequency` | Tần suất tài xế |

---

## Driver (Tài xế)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/driver/context` | Thông tin tài xế |
| GET | `/api/driver/trips` | Danh sách chuyến |
| GET | `/api/driver/trips/{id}` | Chi tiết chuyến |
| POST | `/api/driver/trips/{id}/start` | Bắt đầu chuyến |
| POST | `/api/driver/trips/{id}/complete` | Kết thúc chuyến |
| GET | `/api/driver/tp-days` | Danh sách ngày TP |
| GET | `/api/driver/tp-days/{id}` | Chi tiết ngày TP |
| POST | `/api/driver/tp-days/{id}/confirm` | Xác nhận nhận lịch |
| POST | `/api/driver/tp-days/{id}/start` | Bắt đầu chuyến TP |
| POST | `/api/driver/tp-days/{id}/complete` | Kết thúc chuyến TP |
| POST | `/api/driver/tp-days/{id}/sync` | Sync offline data |

---

## Admin/System

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/admin/users` | Danh sách users |
| GET | `/api/admin/users/search` | Tìm kiếm user |
| POST | `/api/admin/users/bulk-roles` | Cập nhật role hàng loạt |
| GET | `/api/admin/roles` | Danh sách roles |
| POST | `/api/admin/roles` | Tạo role |
| GET | `/api/admin/permissions` | Danh sách permissions |
| GET | `/api/admin/feature-toggles` | Feature flags |
| PUT | `/api/admin/feature-toggles/{name}` | Bật/tắt feature |
| GET | `/api/admin/dispatch-settings` | Cài đặt điều vận |
| PUT | `/api/admin/dispatch-settings/{key}` | Cập nhật setting |
| GET | `/api/system/audit` | System audit log |

---

## Response Format

```json
{
  "data": { ... },  // hoặc null
  "message": "...", // optional
  "errors": { ... } // chỉ khi validation fail (422)
}
```

### Pagination
```json
{
  "items": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### Error codes
| Code | Mô tả |
|------|-------|
| 401 | Chưa xác thực |
| 403 | Không có quyền |
| 404 | Không tìm thấy |
| 409 | Conflict (optimistic lock) |
| 422 | Validation error |
| 429 | Too many requests |

## Checklist tài xế & Hồ sơ chi phí xe

Chi tiết nghiệp vụ: [docs/modules/driver-checklist-expenses.md](../modules/driver-checklist-expenses.md)

### Tài xế (`driver.spa` cho GET; quyền `checklist.submit` / `expense.submit`)

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/driver/checklists/overview` | Kỳ hiện tại daily/weekly, xe NG hôm nay |
| GET | `/api/driver/checklists/options` | Template, xe, chuyến / ngày TP hôm nay |
| GET | `/api/driver/checklists` | Lịch sử phiếu |
| GET | `/api/driver/checklists/{id}` · `/{id}/pdf` | Chi tiết · file in |
| POST | `/api/driver/checklists` | Tạo phiếu `{template_id, vehicle_id, trip_id?, tp_program_day_id?, location_text?}` |
| PATCH | `/api/driver/checklists/{id}` | Lưu nháp `{lock_version, items:[{id,result,note}], trip_info?, note?}` |
| POST | `/api/driver/checklists/{id}/submit` | Nộp `{lock_version, confirm:true, items?}` |
| DELETE | `/api/driver/checklists/{id}` | Hủy nháp |
| POST/DELETE | `/api/driver/checklists/{id}/items/{item}/evidence[/{attachment}]` | Ảnh minh chứng |
| GET | `/api/driver/expenses` · `/defaults` · `/cost-candidates` | Danh sách · mặc định · chi phí có thể đưa vào hồ sơ |
| GET | `/api/driver/expenses/{id}` · `/{id}/pdf` · `/{id}/xlsx` | Chi tiết · file in |
| POST | `/api/driver/expenses` | Tạo `{doc_type, payment_kind?, parent_id?, …, lines[]}` |
| PATCH | `/api/driver/expenses/{id}` | Sửa (draft/rejected) `{lock_version, …}` |
| POST | `/api/driver/expenses/{id}/submit` · `/cancel` · `/signed-scan` | Nộp · hủy · upload scan |

### Back-office (`dispatch.staff`)

| Method | Path | Quyền |
|---|---|---|
| GET | `/api/checklists/compliance?template_id&date_from&date_to` | checklist.view |
| GET | `/api/checklists` · `/export` · `/{id}` · `/{id}/pdf` · `/{id}/xlsx` | checklist.view |
| POST | `/api/checklists/{id}/review` | checklist.view |
| GET | `/api/checklist-settings` | checklist.view |
| PATCH/POST/DELETE | `/api/checklist-settings/templates/{t}[/items[/{item}\|/reorder]]`, `/api/checklist-settings/slots[/{slot}]` | checklist.manage |
| GET | `/api/expenses` · `/{id}` · `/{id}/pdf` · `/{id}/xlsx` · `/settings` | expense.view |
| POST | `/api/expenses/{id}/reject` · `/mark-paid` · `/cancel` · `/signed-scan` | expense.manage |
| PUT | `/api/expenses/settings` | expense.manage |
| GET | `/api/vehicle-cost-ledger?date_from&date_to` · `/{vehicle}` · `/{vehicle}/export` | expense.view |
