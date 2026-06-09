# Quy trình nghiệp vụ — VA Điều Vận

## 1. Quy trình yêu cầu điều vận chuẩn

```
┌─────────────────────────────────────────────────────────────┐
│                  DISPATCH REQUEST WORKFLOW                   │
└─────────────────────────────────────────────────────────────┘

Nhân viên                 Trưởng đơn vị           Điều phối viên
    │                           │                        │
    ├─── Tạo yêu cầu ──────────►│                        │
    │    (Portal/Wizard)        │                        │
    │                           ├─── Duyệt hoặc ────────►│
    │                           │    từ chối             │
    │                           │                        │
    │◄── Thông báo kết quả ─────┤                        │
    │                                                    │
    │                                         ├─── Gán xe/tài xế
    │                                         │
    │                                         ├─── Tài xế nhận lịch
    │                                         │
    │◄── Thông báo chuyến ─────────────────── ┤
    │
    │           [Ngày thực hiện]
    │
    │                          Tài xế
    │                            │
    │                            ├─── Xác nhận nhận chuyến
    │                            ├─── Bắt đầu chuyến (GPS start)
    │                            ├─── Chạy chuyến
    │                            └─── Kết thúc chuyến (GPS end)
    │
    │◄── Thông báo hoàn thành ─────────────────────────────┘
```

## 2. Quy trình Transport Program (TP)

```
┌─────────────────────────────────────────────────────────────┐
│                   TP LIFECYCLE WORKFLOW                      │
└─────────────────────────────────────────────────────────────┘

Giai đoạn 1: Thiết lập
─────────────────────
Admin tạo TpProgram (tên, thời gian, lịch hàng ngày)
    │
    ├── Hệ thống tự sinh TpProgramDay cho từng ngày trong range
    └── Admin gán tài xế mặc định

Giai đoạn 2: Import học sinh
────────────────────────────
Upload Excel file
    │
    ├── Parse → Validate → (Fix lỗi) → Execute
    └── Tạo TpStudent + TpEnrollment records

Giai đoạn 3: Vận hành hàng ngày
─────────────────────────────────
[Buổi sáng, trước giờ chạy]
    Tài xế: Xem danh sách học sinh trong ngày
    Điểm danh báo trước: Phụ huynh/dispatcher đánh vắng
            │
    [Tài xế bắt đầu chuyến]
            │
    StartTripExecutionAction:
    - Tạo TpTripExecution
    - Tạo TpTripStudentLog cho mỗi học sinh enrolled
    - Học sinh vắng báo trước → initial_status='pre_absent'
            │
    [Trên xe]
    Tài xế: Scan/check-in từng học sinh
    - Học sinh lên xe → boarded_at, final_status='boarded'
    - Học sinh vắng   → absent_at,  final_status='absent'
            │
    [Kết thúc chuyến]
    Tài xế: Complete trip
    Hệ thống: Total_absent count

Giai đoạn 4: Điểm danh chính thức (sau chuyến)
──────────────────────────────────────────────────
Dispatcher xem danh sách điểm danh ngày
    │
    ├── Điều chỉnh vắng/có mặt
    ├── Ghi lý do vắng
    ├── Lưu nháp (DRAFT)
    └── Xác nhận (CONFIRMED) ← optimistic lock check
```

## 3. Quy trình hàng hóa (Cargo)

```
Portal tạo yêu cầu loại 'cargo'
    │
    ├── Điền thông tin kiện hàng (cargoRows)
    ├── Hệ thống tính SLA delivery time
    │
    └── [Quá SLA] CargoSlaCheckCommand → CargoSlaBreachedNotification
```

## 4. Quy trình điều vận định kỳ (Recurring)

```
Admin tạo DispatchRequest loại 'recurring'
    │
    ├── Cấu hình: ngày trong tuần, số tuần
    │
[Hàng ngày - Cron]
MaterializeRecurringDispatchRequestsCommand
    │
    └── Tạo instance DispatchRequest từ recurring template
        với recurring_parent_id = template.id
```

## 5. Quy trình thanh toán / quyết toán

```
Trip hoàn thành
    │
    ├── TripCost records được tạo (chi phí từng loại)
    ├── Accountant review
    │
ReconciliationPeriod mở
    │
    ├── Accountant tổng hợp trips trong period
    ├── Mark trips as 'paid' → payment_status='paid', paid_at=now()
    │
    └── FinancialDataLock block mọi sửa đổi chi phí/hành khách
```

## 6. Quy trình biên bản ký (Signed Documents)

```
Chuyến hoàn thành
    │
POST /api/signed-documents/upload
    │
SignedDocumentUploadService
    │
ProcessSignedDocumentPipelineJob (Queue)
    │
SignedDocumentPipelineService
    │
    ├── OCR extraction (DocumentOcrEngine)
    ├── Signature detection (SignatureDetectionService)
    └── Verification (SignedDocumentVerificationService)
```
