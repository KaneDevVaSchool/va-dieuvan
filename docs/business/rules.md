# Business Rules — VA Điều Vận

## Quy tắc điều vận

### BR-1: Thứ tự duyệt yêu cầu
Luồng: `pending` → (dept_head duyệt) → `approved` → (dispatcher điều phối) → Trip created
- Yêu cầu từ portal có thể yêu cầu trưởng đơn vị duyệt trước
- Yêu cầu khẩn (`is_urgent=true`) được ưu tiên xử lý

### BR-2: Gán xe/tài xế
- Một chuyến chỉ có 1 xe và 1 tài xế chính
- Có thể gán thêm `supplement_transports` (bổ sung)
- Có thể dùng xe thuê ngoài (`transport_provider_id`) thay vì xe nội bộ

### BR-3: Optimistic Locking
- `trips.lock_version` tăng mỗi lần update
- Client phải gửi `lock_version` hiện tại khi update
- Nếu version không khớp → 409 Conflict

### BR-4: Financial Lock
- Khi `trips.payment_status = 'paid'`: KHÔNG được sửa hành khách hoặc chi phí
- Khi `trips.paid_at` đã set: chuyến đã khóa tài chính

### BR-5: Idempotency
- Mutations quan trọng hỗ trợ `X-Idempotency-Key` header
- Cùng key → response cached 24h, không execute lại

---

## Quy tắc Transport Program (TP)

### TP-BR-1: Enrollment date-aware
- Học sinh chỉ được tính là "enrolled" vào ngày D nếu:
  - `enrolled_at <= D` VÀ (`unenrolled_at IS NULL` OR `unenrolled_at > D`)

### TP-BR-2: Attendance confirmation block
- Không thể xác nhận điểm danh nếu còn học sinh vắng chưa có lý do (`missing_reason_count > 0`)

### TP-BR-3: Attendance lock version
- `attendance_lock_version` check trước khi confirm
- Nếu version mismatch → 409 (ai đó đã thay đổi trong lúc bạn đang thao tác)

### TP-BR-4: Shift support
- Chương trình có thể có ca sáng + ca chiều
- Mỗi ca có attendance session riêng biệt
- Lưu trữ: `morning_attendance_*` và `afternoon_attendance_*` columns

### TP-BR-5: Auto-sync execution
- Khi đánh vắng qua dispatcher trong lúc chuyến đang chạy (`TpTripExecution.status = 'in_progress'`):
  - Tự động cập nhật `TpTripStudentLog.final_status = 'absent'`
  - Tăng `TpTripExecution.total_absent`

### TP-BR-6: Start trip conditions
- Chỉ bắt đầu được khi chương trình đang `active`
- Ngày phải có execution chưa được start
- Tài xế phải được gán cho ngày đó

---

## Quy tắc hàng hóa (Cargo)

### CARGO-BR-1: SLA tracking
- Mỗi lô hàng có thời hạn giao (`delivery_at`)
- `CargoSlaCheckCommand` chạy hourly để kiểm tra
- Nếu quá SLA: gửi `CargoSlaBreachedNotification`

---

## Quy tắc tài chính

### FIN-BR-1: Không xóa records tài chính
- `payments`, `trip_costs`, `reconciliation_periods` không bao giờ hard delete

### FIN-BR-2: Quyết toán theo kỳ
- `ReconciliationPeriod` gộp các trips trong khoảng thời gian
- Sau khi mark paid → `FinancialDataLock` block mọi thay đổi

---

## Quy tắc điều vận định kỳ (Recurring)

### REC-BR-1: Materialize hàng ngày
- `MaterializeRecurringDispatchRequestsCommand` chạy daily
- Tạo instance từ recurring template với ngày thực tế
- `recurring_parent_id` liên kết về template gốc

### REC-BR-2: Budget tracking
- `DispatchPackage` theo dõi ngân sách recurring
- Cảnh báo khi sắp hết ngân sách (`DispatchPackageSessionsLowBalanceNotification`)
- Block khi vượt ngân sách (`RecurringBudgetExceededNotification`)

---

## Quy tắc biên bản ký (Signed Documents)

### SD-BR-1: Pipeline xử lý
- Upload → OCR → Signature Detection → Verification
- Xử lý async qua Queue job (`ProcessSignedDocumentPipelineJob`)

### SD-BR-2: Dispatch sync
- Biên bản ký được sync vào DispatchRequest paper status
- `paper_status`: `pending` → `received`
