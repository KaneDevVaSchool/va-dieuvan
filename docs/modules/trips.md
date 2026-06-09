# Module: Quản lý chuyến đi (Trips)

## Mô tả nghiệp vụ

Module Trip quản lý vòng đời đầy đủ của một chuyến xe, từ khi được tạo từ yêu cầu điều vận cho đến khi hoàn thành và thanh toán.

## Trạng thái chuyến đi

```
pending → approved → assigned → driver_confirmed → in_progress → completed
                  ↘ cancelled                                  ↘ incident
```

| Status | Mô tả |
|--------|-------|
| `pending` | Mới tạo, chờ điều phối |
| `approved` | Đã duyệt |
| `assigned` | Đã gán xe/tài xế |
| `driver_confirmed` | Tài xế xác nhận |
| `in_progress` | Đang thực hiện |
| `completed` | Hoàn thành |
| `incident` | Có sự cố |
| `cancelled` | Đã hủy |

## Database

### Bảng chính
- `trips` — Thông tin chuyến
- `trip_records` — Kết quả thực tế (odometer, distance)
- `trip_costs` — Chi phí phát sinh
- `trip_events` — Log sự kiện
- `trip_passengers` — Danh sách hành khách đặt tên

### Quan hệ
```
trips → dispatch_requests (N:1) — nguồn yêu cầu
trips → vehicles (N:1) — xe được gán
trips → drivers (N:1) — tài xế được gán
trips → transport_providers (N:1) — đơn vị thuê ngoài
trips → trip_records (1:1) — kết quả thực tế
trips → trip_costs (1:N) — chi phí
trips → trip_events (1:N) — sự kiện
trips → trip_passengers (1:N) — hành khách
```

## API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/trips` | Danh sách chuyến (paginated) |
| GET | `/api/trips/stats` | Thống kê KPI |
| GET | `/api/trips/{id}` | Chi tiết chuyến |
| POST | `/api/trips/{id}/assign` | Gán xe/tài xế |
| POST | `/api/trips/{id}/reschedule` | Đổi lịch |
| POST | `/api/trips/{id}/start` | Bắt đầu chuyến |
| POST | `/api/trips/{id}/complete` | Kết thúc chuyến |
| POST | `/api/trips/{id}/passenger-list` | Cập nhật danh sách HK |
| POST | `/api/trips/{id}/passenger-check-in/{key}` | Check-in hành khách |
| POST | `/api/trips/{id}/duplicate` | Nhân bản yêu cầu |

## Permission

- `trips.view` — Xem danh sách chuyến
- `trips.assign` — Gán xe/tài xế
- `trips.reschedule` — Thay đổi lịch trình
- `trips.manage_costs` — Quản lý chi phí

## Business Rules

1. **Optimistic Locking**: `lock_version` field chống race condition khi nhiều dispatcher cùng cập nhật
2. **Financial Lock**: Chuyến đã thanh toán (`payment_status=paid`) không cho sửa hành khách hoặc chi phí
3. **TripVisibility**: Admin/Dispatcher thấy tất cả; tài xế chỉ thấy chuyến của mình
4. **Passenger Types**: Mỗi `trip_type` có format data khác nhau trong `wizard_snapshot`:
   - `door_to_door/point_to_point`: `passengerRows[]`
   - `business`: `businessRows[] + passengerRows[]`
   - `cargo`: `cargoRows[]`
5. **Duplicate**: Cho phép nhân bản DispatchRequest từ Trip để tạo chuyến tương tự

## Known Issues

1. **Trùng lặp `$trip->load()`**: TripController::updatePassengerList() có 2 nhánh đều gọi cùng một `$trip->load([...])` array — nên extract ra method riêng
2. **Missing transaction**: Phần xử lý `cargo/business/point_to_point` trong `updatePassengerList()` không wrap trong DB::transaction() nhưng modify nhiều fields
3. **Normalize methods trong Controller**: `normalizePassengerRows()`, `normalizeBusinessRows()`, `normalizeCargoRows()` nên chuyển vào Service hoặc DTO
