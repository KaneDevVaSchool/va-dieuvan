# Portal — Đề xuất chuyến định kỳ (CLB / ngoại khóa)

Tài liệu nghiệp vụ cho stakeholder. Phiên bản đồng bộ với triển khai hệ thống.

## Phạm vi

- **Yêu cầu mới (portal chung):** `/portal/new` — công tác, hàng, cửa–cửa, điểm–điểm lẻ; **không** tạo CLB định kỳ tại đây.
- **Tạo CLB định kỳ:** `/portal/extracurricular/new` — module **Kế hoạch định kỳ** (UI riêng, không dùng wizard portal chung).
- **Danh sách CLB:** `/portal/extracurricular/requests` — từng chuyến (instance) sinh từ mẫu định kỳ.

## Luồng tạo (UI mới)

1. **Lịch định kỳ** — hai chế độ:
   - **Theo khoảng ngày:** mỗi ngày lịch từ ngày bắt đầu → ngày kết thúc (inclusive); giờ đi / giờ về.
   - **Theo tuần / thứ:** lặp các thứ đã chọn trong khoảng; kết thúc theo **ngày** hoặc **số tuần**.
   - **Preview:** hiển thị số chuyến và danh sách ngày trước khi gửi.
2. **Tuyến** — điểm đón, điểm trả, số HS kế hoạch (một cặp P2P).
3. **Người đề nghị** — thông tin liên hệ (không có đối tượng tham gia / người phối hợp).
4. **Mục đích & căn cứ** — mô tả và file (nếu có).
5. **Xác nhận** — tóm tắt lịch + gửi mẫu định kỳ.

Gợi ý khi nút bị khóa: danh sách thiếu sót hiển thị cạnh nút «Tiếp tục» / «Gửi».

Sau xác nhận → hệ thống tạo **mẫu** + **chuyến đầu** + **materialize** các chuyến còn lại trong chuỗi (khi có ngày/tuần kết thúc).

## Luồng số HS thực tế (hai bước)

| Bước | Hành động | Điều kiện |
|------|-----------|-----------|
| 1 | **Lưu** số HS thực tế | Instance định kỳ; còn ≥ 24h trước `depart_at`; chưa gửi chốt |
| 2 | **Gửi điều vận** (chốt) | Đã lưu số thực tế; cùng điều kiện 24h; chưa gửi trước đó |

## Trạng thái theo dõi (UI)

1. **Chưa cập nhật** — chưa có số HS thực tế.
2. **Đã cập nhật** — đã lưu số, chưa gửi chốt.
3. **Đã gửi điều vận** — đã chốt số HS.
4. **Đã xác nhận điều phối** — phiếu đã duyệt và chuyến xe ở trạng thái vận hành.

## Quyền

- **Người đề xuất:** lưu + gửi chốt trên phiếu của mình (trong cửa sổ 24h).
- **Điều vận:** xem phiếu đã chốt; có thể chỉnh số HS sau khóa (override).
