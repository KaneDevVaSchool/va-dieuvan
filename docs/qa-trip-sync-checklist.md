# QA — Đồng bộ chuyến / chi phí / giờ (baseline hiện tại)

Dùng sau mỗi release hoặc khi đổi API trạng thái.

## 1. Ma trận trạng thái chuyến

| Hành động | API mong đợi | Ghi chú kiểm tra |
|-----------|----------------|------------------|
| Tài xế nhận (`driver_confirmed`) | `POST /trips/{id}/status` body `status: driver_confirmed` | DB `trips.status`; timeline điều vận; tài xế thấy trong lịch |
| Tài xế từ chối | `status: cancelled` + `message` | Admin: chuyến huỷ; không còn trên timeline “đang chạy” |
| Tài xế bắt đầu | `in_progress` | Admin trip detail + board cập nhật (poll hoặc tải lại) |
| Tài xế hoàn thành | `completed` | Board: chuyến không còn trên dải lịch (filter `timelineTrips`); trip detail hiển thị completed |

Kiểm tra thêm: `trip_events` có bản ghi `status_change` với `data.from` / `data.to`.

## 2. Chi phí chuyến

| Bước | Kỳ vọng |
|------|---------|
| Tài xế gửi chi phí | `trip_costs.status = submitted` |
| Admin Costs: lọc chờ duyệt | Mặc định `/costs` ưu tiên hàng chờ `submitted` (xem query `status`) |
| Duyệt | `POST /trip-costs/{id}/decision` `decision: confirm` → `confirmed` |
| Từ chối | `decision: reject` + `reason` (tuỳ chọn) → `rejected` |
| Tổng trên trip (admin CostTracker) | Chỉ cộng `confirmed` cho breakdown; hiện phần chờ riêng |

## 3. Giờ Việt Nam

| Kiểm tra | Pass nếu |
|-----------|----------|
| Chi tiết chuyến (tài xế) | Dòng giờ 24h, timezone note `UTC+7`, cùng “instant” với admin cho cùng ISO |
| Ca đêm | `depart_at` gần nửa đêm không lệch ngày so với admin (cùng `Asia/Ho_Chi_Minh`) |

## 4. Ghi nhận kết quả

- Ngày / môi trường / người test  
- Pass/Fail từng mục + link issue nếu fail  
