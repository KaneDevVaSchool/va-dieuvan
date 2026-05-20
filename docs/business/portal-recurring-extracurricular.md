# Portal — Đề xuất chuyến định kỳ (CLB / ngoại khóa)

Tài liệu nghiệp vụ cho stakeholder. Phiên bản đồng bộ với triển khai hệ thống.

## Phạm vi

- **Yêu cầu mới (portal chung):** `/portal/new` — công tác, hàng, cửa–cửa, điểm–điểm lẻ; **không** tạo CLB định kỳ tại đây.
- **Tạo CLB định kỳ:** `/portal/extracurricular/new` — lịch lặp (tuần, thứ, giờ đi/về, kết thúc), tuyến P2P ngoại khóa, số HS kế hoạch.
- **Danh sách CLB:** `/portal/extracurricular/requests` — từng chuyến (instance) sinh từ mẫu định kỳ.

## Luồng tạo

1. Trên form module **không** nhập «Ngày đề xuất» / «Ngày giờ cần xe» — chỉ **lịch lặp** (ngày bắt đầu chuỗi, **giờ đi**, **giờ về**, thứ trong tuần, kết thúc theo ngày hoặc số tuần); giờ đồng bộ sang bước Chi tiết (điểm đón/trả).
2. Người đề xuất xác nhận → hệ thống tạo **mẫu** + **chuyến đầu** + **materialize** các chuyến còn lại trong chuỗi.
3. Mỗi chuyến: trạng thái duyệt riêng (`pending` …), số HS kế hoạch trên `passenger_count`.

Hai luồng tạo tách route, component entry (`PortalGeneralCreateView` / `PortalExtracurricularCreateView`) và bản nháp localStorage riêng.

## Luồng số HS thực tế (hai bước)

| Bước | Hành động | Điều kiện |
|------|-----------|-----------|
| 1 | **Lưu** số HS thực tế | Instance định kỳ; còn ≥ 24h trước `depart_at`; chưa gửi chốt |
| 2 | **Gửi điều vận** (chốt) | Đã lưu số thực tế; cùng điều kiện 24h; chưa gửi trước đó |

Sau **Gửi điều vận:** khóa chỉnh sửa (requester), audit + thông báo dispatcher.

## Trạng thái theo dõi (UI)

1. **Chưa cập nhật** — chưa có số HS thực tế.
2. **Đã cập nhật** — đã lưu số, chưa gửi chốt.
3. **Đã gửi điều vận** — đã chốt số HS.
4. **Đã xác nhận điều phối** — phiếu đã duyệt và chuyến xe ở trạng thái vận hành (gán xe / đang chạy).

Trạng thái duyệt phiếu (`pending`, `approved`, …) tách biệt với trạng thái trên.

## Quyền

- **Người đề xuất:** lưu + gửi chốt trên phiếu của mình (trong cửa sổ 24h).
- **Điều vận:** xem phiếu đã chốt; có thể chỉnh số HS sau khóa (override).

## Lịch sử

- Audit khi cập nhật số HS và khi gửi chốt (`request.student_count_actual_updated`, `request.student_count_submitted`).
