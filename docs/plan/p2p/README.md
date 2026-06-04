# P2P Redesign — Tài liệu Kế hoạch

> **Module:** Hệ thống Đưa đón Học sinh  
> **Phiên bản:** 1.0  
> **Cập nhật:** 05/06/2026  
> **Trạng thái:** Đang thiết kế — chưa triển khai

---

## Mục lục

| File | Nội dung |
|------|----------|
| [00-brief.md](./00-brief.md) | Bối cảnh, vấn đề, mục tiêu, phạm vi |
| [01-plan.md](./01-plan.md) | Kế hoạch tổng thể, phases, domain model, schema |
| [02-implementation.md](./02-implementation.md) | Checklist triển khai — DB, Backend, Frontend, Mobile |
| [03-edge-cases.md](./03-edge-cases.md) | Edge cases và quyết định đã chốt |
| [04-enhancements.md](./04-enhancements.md) | Đề xuất nâng cấp và mở rộng tính năng |

---

## Tóm tắt nhanh

**Vấn đề cốt lõi:** Hệ thống cũ (`policy_trips` + cron job) thiết kế xoay quanh **chuyến xe**. Thực tế người dùng quản lý **chương trình đưa đón** và **danh sách học sinh theo ngày**.

**Giải pháp:** Chuyển sang mô hình `Program → Day → Enrollment → Execution`, tách biệt hoàn toàn Plan và Runtime.

**Table prefix:** Toàn bộ bảng mới dùng prefix `tp_` — tồn tại song song với bảng `policy_*` cũ cho đến khi migration hoàn tất.

---

## Quyết định kiến trúc nhanh

| Quyết định | Cũ | Mới |
|-----------|-----|-----|
| Core entity | `policy_trips` | `tp_programs` |
| Schedule generation | Cron 22:00 hàng ngày | Sync tại thời điểm tạo program |
| Attendance storage | Dense (tất cả học sinh) | Sparse (chỉ lưu vắng) |
| Driver runtime | Gộp vào `policy_trips` | Tách `tp_trip_executions` |
| Driver default | Không có | Kế thừa từ program, override per-day |
| Import | Không có | 5-step enterprise pipeline |
| Table namespace | `policy_*` | `tp_*` (parallel, không đụng cũ) |
