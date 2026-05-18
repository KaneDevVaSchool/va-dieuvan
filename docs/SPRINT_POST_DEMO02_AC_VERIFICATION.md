# Sprint Post Demo 02 — Acceptance Criteria Verification

Tài liệu đối chiếu nhanh sau khi implement refactor + `dispatch_package_cost_alert`. Chạy thủ công trên staging/local và đánh dấu.

## Task 2.1 — Luồng duyệt 5 bước & PDF

| AC | Mô tả | Cách verify | Pass |
|----|--------|-------------|------|
| AC1 | Không tải PDF khi chưa `approved` | Tạo phiếu P2P (không D2D), mở chi tiết, nút PDF xám; thử gọi API trực tiếp nếu có | ☐ |
| AC2 | Fill giá → `price_filled` | Đăng nhập Điều vận, nhập giá, lưu; kiểm tra badge/trạng thái | ☐ |
| AC3 | Push Trưởng đơn vị | Kiểm tra notification khi chuyển `price_filled` (thiết bị + queue) | ☐ |
| AC4 | Từ chối bắt buộc lý do | Trưởng ĐV, mở modal, Submit để trống → báo lỗi; nhập lý do → OK | ☐ |
| AC5 | Sau duyệt PDF bật | Sau `approved`, nút PDF không disabled | ☐ |
| AC6 | Điều phối không chờ PDF | Tạo chuyến / gán ngay sau approved | ☐ |
| AC7 | Upload scan ≤10MB | Requester upload PDF/JPG/PNG; thử file quá lớn | ☐ |

## Task 3.1 — Recurring & sĩ số

| AC | Mô tả | Pass |
|----|--------|------|
| AC1 | Sinh phiếu theo lịch | ☐ |
| AC2 | Sĩ số >24h / khóa ≤24h | ☐ |
| AC3 | Điều vận override sĩ số | ☐ |
| AC4 | Cảnh báo gói (`CostLimitAlert`) khi gần hết / hết buổi trong gói | ☐ |
| AC5 | Đặt lại → phiếu mới | ☐ |

## Task 3.2 — Bảng giá tham chiếu

| AC | Pass |
|----|------|
| AC1–AC4 | ☐ (link cạnh ô giá, tab mới, settings, ẩn khi chưa config) |

## Task 3.3 — Giấy tờ xe

| AC | Pass |
|----|------|
| AC1–AC4 | ☐ |

---
*Cập nhật: theo implementation plan (StatusBadge, tách component, `dispatch_package_cost_alert`).*
