# Portal — Kế hoạch định kỳ (CLB / ngoại khóa)

Tài liệu nghiệp vụ cho stakeholder. Phiên bản đồng bộ với triển khai hệ thống.

## Phạm vi

- **Yêu cầu mới (portal chung):** `/portal/new` — không tạo CLB định kỳ tại đây.
- **Tạo kế hoạch định kỳ:** `/portal/extracurricular/new` — form tối giản (lịch + tuyến + ghi chú).
- **Danh sách CLB:** `/portal/extracurricular/requests` — lịch, bảng lịch trình, hoặc bảng chi tiết (số HS inline).

## Luồng tạo

Người dùng nhập:

- Ngày bắt đầu, ngày kết thúc lặp
- Giờ đi / giờ về
- Các thứ trong tuần áp dụng (ít nhất một)
- Điểm đón, điểm trả
- Ghi chú (tuỳ chọn)

Người đề xuất = tài khoản đăng nhập (không nhập form liên hệ / mục đích / số HS kế hoạch lúc tạo).

Preview hiển thị số chuyến và danh sách ngày trước khi **Tạo kế hoạch**.

Sau tạo → mẫu `dispatch_request_template` + materialize đủ phiếu `dispatch_request` độc lập trong chuỗi (không trùng mốc khởi hành).

## Sửa kế hoạch (regenerate)

`PATCH /api/portal/dispatch-request-templates/{id}` cập nhật mẫu và **sync** instance:

- Thêm phiếu pending thiếu
- Hủy (`cancelled`) phiếu pending thừa ngoài lịch mới — chỉ khi chưa chốt số HS và chưa có chuyến xe
- Giữ nguyên phiếu đã `student_count_submitted_at` hoặc đã có trip

## Sửa từng phiếu (trước 24h)

Trên chi tiết phiếu (`/portal/extracurricular/requests/:id`):

- Form có cấu trúc: chuyến, thời gian, tuyến, số HS, ghi chú, gợi ý điều phối
- `PATCH /api/portal/dispatch-requests/{id}/recurring-instance`
- `POST /api/portal/dispatch-requests/{id}/submit-recurring` — chốt gửi điều vận (cần đã lưu số HS thực tế)

Điều kiện: còn ≥ 24h trước `depart_at`, chưa chốt, trạng thái pending / price_filled.

## Trạng thái theo dõi (UI)

1. **Chưa cập nhật** — chưa có số HS thực tế.
2. **Đã cập nhật** — đã lưu số, chưa gửi chốt.
3. **Đã gửi điều vận** — đã chốt số HS.
4. **Đã xác nhận điều phối** — phiếu đã duyệt và chuyến xe vận hành.

## Quyền

- **Người đề xuất:** lưu + gửi chốt trên phiếu của mình (trong cửa sổ 24h).
- **Điều vận:** xem phiếu đã chốt; có thể chỉnh số HS sau khóa (override qua SPA staff).
