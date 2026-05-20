# TASK 3.1 — Recurring: Cập nhật số lượng học sinh cho chuyến định kỳ

> **Module:** P2P Ngoại khóa  
> **Ưu tiên:** Cao  
> **Loại:** Tính năng mới — thiết kế + phát triển mới  
> **FR liên quan:** FR-P01 — Module P2P Ngoại khóa (SRS v1.3)

---

## Bối cảnh nghiệp vụ

Các câu lạc bộ ngoại khóa (ví dụ: học bơi) có lịch cố định theo tuần — thời gian, địa điểm, lộ trình không đổi, nhưng số lượng học sinh có thể thay đổi mỗi tuần. Hiện tại người đề xuất phải tạo phiếu mới mỗi tuần, tốn thời gian và dễ sai sót.

**Mục tiêu:** Hệ thống hỗ trợ tạo lịch định kỳ một lần; người đề xuất chỉ cần cập nhật số lượng học sinh trước mỗi chuyến.

---

## 3.1.a — Cơ chế Recurring trên phiếu đề xuất

### Mô tả

Khi tạo phiếu loại **P2P Ngoại khóa**, bổ sung toggle **"Đề xuất định kỳ"** (mặc định: tắt).

### Khi bật toggle — hiển thị form cấu hình lặp

| Trường | Kiểu dữ liệu | Bắt buộc | Ghi chú |
|--------|-------------|----------|---------|
| `repeat_days` | `array<enum>` (Mon–Sun) | ✓ | Checkbox chọn nhiều ngày trong tuần |
| `departure_time` | `time` | ✓ | Giờ đi (HH:mm) |
| `return_time` | `time` | ✓ | Giờ về (HH:mm) |
| `start_date` | `date` | ✓ | Ngày bắt đầu chuỗi |
| `end_date` | `date` | — | Ngày kết thúc. Bắt buộc nếu `repeat_count` để trống |
| `repeat_count` | `integer` | — | Số tuần lặp. Bắt buộc nếu `end_date` để trống |

> **Validate:** `end_date` hoặc `repeat_count` phải có ít nhất một giá trị.

### Sinh chuyến tự động

- Hệ thống sinh danh sách chuyến (`trip`) theo cấu hình khi người dùng lưu phiếu.
- Mỗi chuyến là một bản ghi độc lập — có `status`, `student_count_actual`, `note` riêng.
- Tất cả chuyến trong chuỗi cùng `recurring_group_id`.

### Data model gợi ý

```
RecurringGroup
  id                  uuid PK
  request_id          uuid FK → Request
  repeat_days         json        -- ["MON","WED","FRI"]
  departure_time      time
  return_time         time
  start_date          date
  end_date            date
  created_at          timestamp

Trip
  id                  uuid PK
  recurring_group_id  uuid FK → RecurringGroup (nullable cho chuyến thường)
  trip_date           date
  student_count_plan  integer
  student_count_actual integer
  note                text
  status              enum(pending, confirmed, completed, cancelled)
  locked_at           timestamp   -- set khi qua ngưỡng 24h
```

### Hiển thị phía Điều vận

- Danh sách chuyến hỗ trợ **filter theo tuần / tháng**.
- Hiển thị badge `[Định kỳ]` trên mỗi chuyến thuộc `RecurringGroup`.
- Có thể expand xem tất cả chuyến trong cùng chuỗi từ màn hình chi tiết.

---

## 3.1.b — Cập nhật số lượng học sinh theo chuyến

### Luồng cập nhật

```
Người đề xuất mở chuyến
        │
        ├── còn > 24h trước giờ khởi hành?
        │         │
        │        YES ──→ Trường "Số lượng học sinh thực tế" EDITABLE → Lưu thành công
        │         │
        │         NO ──→ Trường bị LOCK (readonly)
        │                Hiển thị: "Đã qua thời hạn cập nhật —
        │                           liên hệ Điều vận nếu cần thay đổi khẩn"
        │
        └── Điều vận: LUÔN có quyền chỉnh sửa bất kỳ lúc nào
```

### Business rules

| Rule | Chi tiết |
|------|---------|
| BR-1 | Thời hạn cập nhật = `trip_date + departure_time - 24 hours` |
| BR-2 | Khi `now >= deadline` → set `trip.locked_at = now`, field readonly với Người đề xuất |
| BR-3 | Role `DISPATCHER` bỏ qua kiểm tra `locked_at`, luôn được edit |
| BR-4 | Sau khi lock, audit log ghi nhận mọi thay đổi từ Điều vận |

### API

```
PATCH /api/trips/:trip_id/student-count
Body: { "student_count_actual": 25 }

Response 200: { "trip_id": "...", "student_count_actual": 25, "updated_by": "..." }
Response 403: { "error": "TRIP_LOCKED", "message": "Đã qua thời hạn cập nhật..." }
```

> **Chú ý:** middleware kiểm tra `locked_at` trước khi cho phép update — không xử lý ở FE đơn thuần.

---

## 3.1.c — Cảnh báo vượt ngưỡng chi phí gói

### Cấu trúc gói dịch vụ

```
ServicePackage
  id              uuid PK
  name            string      -- "Gói học bơi HK1"
  monthly_budget  decimal     -- 10,000,000 VND
  group_id        uuid FK → RecurringGroup
```

### Logic tính toán

```
monthly_cost = SUM(trip.cost)
    WHERE trip.recurring_group_id = :group_id
      AND MONTH(trip.trip_date) = :current_month
      AND trip.status NOT IN ('cancelled')
```

### Trigger cảnh báo

- Tính lại `monthly_cost` mỗi khi: chuyến được tạo mới, cost được cập nhật, chuyến bị hủy.
- Nếu `monthly_cost > service_package.monthly_budget` → tạo `Alert`.

### Nội dung cảnh báo

```
Title:   Cảnh báo vượt ngưỡng chi phí gói
Message: Chi phí gói [tên gói] tháng [X] đã vượt ngưỡng [Y VND].
         Vui lòng tạo đề xuất bổ sung để phê duyệt thêm ngân sách.
```

### Phân phối cảnh báo

| Kênh | Đối tượng |
|------|----------|
| In-app notification | Người đề xuất + NV Điều vận |
| Toast khi mở phiếu | Người đề xuất (nếu phiếu thuộc gói đã vượt) |
| Badge trên màn hình Điều vận | NV Điều vận |

> **Lưu ý:** Cảnh báo **không block** tạo chuyến mới — chỉ thông báo để các bên chủ động xử lý.

---

## 3.1.d — Tính năng "Đặt lại" (Clone phiếu)

### Điều kiện hiển thị nút

Nút **"Đặt lại"** xuất hiện khi phiếu đề xuất có `status IN ('completed', 'approved')`.

### Hành vi

```
User bấm "Đặt lại"
        │
        ▼
Hệ thống tạo Request mới (status = draft)
        │
        ├── Prefill từ phiếu gốc:
        │     service_type, route_from, route_to,
        │     student_count_plan, note, locations
        │
        ├── Để trống / cần user nhập:
        │     trip_date (bắt buộc), student_count_actual,
        │     và bất kỳ trường nào thay đổi
        │
        └── Redirect → màn hình tạo phiếu (pre-filled)
```

### API

```
POST /api/requests/:source_id/clone
Response 201: { "new_request_id": "...", "prefilled_fields": [...] }
```

### Ràng buộc

- Phiếu clone **không kế thừa** `status`, `approved_by`, `approved_at`, hay bất kỳ dữ liệu duyệt nào từ phiếu gốc.
- Phiếu clone đi qua **đầy đủ luồng duyệt 5 bước** như phiếu thường.
- `cloned_from_id` lưu reference về phiếu gốc (phục vụ audit/traceability).

---

## Acceptance Criteria

| # | Criteria | Ghi chú |
|---|----------|---------|
| AC1 | Tạo phiếu P2P Ngoại khóa có thể bật Recurring với cấu hình lặp theo ngày trong tuần | |
| AC2 | Hệ thống sinh đúng số chuyến theo cấu hình; mỗi chuyến hiển thị riêng trong lịch Điều vận | |
| AC3 | Người đề xuất cập nhật được số lượng học sinh khi còn > 24h trước giờ khởi hành | |
| AC4 | Cập nhật khi còn < 24h → bị block, hiển thị thông báo rõ ràng, không cho phép lưu | Validate phía BE, không chỉ FE |
| AC5 | Điều vận cập nhật được số lượng bất kỳ lúc nào | |
| AC6 | Cảnh báo vượt ngưỡng chi phí gói hiển thị đúng lúc cho đúng đối tượng (Người đề xuất & Điều vận) | |
| AC7 | Nút "Đặt lại" sinh phiếu mới với dữ liệu prefill đầy đủ từ phiếu gốc | |
| AC8 | Phiếu clone đi qua đầy đủ luồng duyệt 5 bước | |

---

## Out of scope (v1)

- Chỉnh sửa hàng loạt (bulk edit) nhiều chuyến trong chuỗi cùng lúc.
- Xóa / hủy toàn bộ chuỗi Recurring (xử lý ở sprint sau).
- Thông báo email cho cảnh báo vượt ngưỡng (chỉ in-app ở v1).

---

*Last updated: 20/05/2026 — VA Schools · Team Application Software*
