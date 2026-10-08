# Module: Checklist tài xế & Hồ sơ chi phí xe

Pilot 10/2026. Kế hoạch gốc: [docs/plans/driver-checklist-and-expenses.md](../plans/driver-checklist-and-expenses.md).
Dữ liệu nguồn: `data/Dữ liệu tham khảo - Phần mềm Điều vận.xlsx`.

## Bật / tắt & quyền

| Feature toggle | Ý nghĩa |
|---|---|
| `module.driver_checklist` | Toàn bộ checklist (API trả 403 khi tắt, menu ẩn) |
| `module.driver_expense` | Toàn bộ hồ sơ chi phí + sổ chi phí theo xe |

| Quyền | Role mặc định (migration `2026_10_01_100400`, gán bổ sung, không sync) |
|---|---|
| `checklist.submit` | driver |
| `checklist.view`, `checklist.manage` | dispatcher, admin |
| `expense.submit` | driver |
| `expense.view` | dispatcher, accountant, admin |
| `expense.manage` | accountant, admin |

> Route back-office nằm sau middleware `dispatch.staff` → user cần thêm role vào được SPA điều vận (admin / dispatcher). Role `accountant` đơn lẻ hiện chưa vào được SPA (hành vi có sẵn của hệ thống).

## Mã chứng từ

`App\Support\DocumentCodeGenerator::next($prefix)` → `{PREFIX}-{YYMMDD}-{n}`, `n` reset mỗi ngày (giờ VN), đếm riêng theo tiền tố, khóa dòng `document_sequences` khi cấp số.

| Tiền tố | Chứng từ |
|---|---|
| `CL` | Phiếu checklist |
| `DX` | Phiếu đề xuất (BM.01/MH.QT.01) |
| `TT` / `TU` / `HU` | Đề nghị thanh toán / tạm ứng / hoàn ứng (BM03-PKT-QT-TUTT) |
| `BK` | Bảng kê mua vào không có hóa đơn (01/TNDN) |

## Checklist Daily / Weekly

- Template seed từ sheet "Nội dung Checklist" (`ChecklistTemplateSeeder`): `daily_safety` 30 mục, `weekly_compliance` 16 mục. Nhóm "Thông tin chuyến" → `trip_info`; "Kết luận" → tính tự động.
- Tài xế làm **bất kỳ lúc nào, nhiều phiếu/ngày** (mỗi xe / ca / chuyến). Kỳ (`period_key`: `Y-m-d` hoặc `o-\WW`) tính "đã làm" khi có ≥ 1 phiếu đã nộp.
- Mục được **snapshot** vào `checklist_submission_items` khi tạo phiếu; sửa template tăng `version`, không đổi lịch sử.
- Mốc thời gian lấy **giờ server**: `started_at`, `checked_at` từng mục, `submitted_at`. `client_submitted_at` chỉ để đối chiếu.
- Nộp: mọi mục phải có kết quả; NG bắt buộc ghi chú; NG + `requires_photo_on_ng` bắt buộc ảnh (attachment `checklist_evidence` trên item).
- Kết luận: có ≥ 1 mục Critical NG → `fail`. **Không chặn chuyến** — gửi `ChecklistCriticalFailedNotification` cho điều phối, banner cảnh báo trên app tài xế.
- Mục giấy tờ có `auto_source` (`driver_doc:license`, `vehicle_doc:inspection_certificate`…) hiển thị gợi ý theo hạn trong kho chứng từ; tài xế vẫn tự bấm.
- Mẫu in daily: 7 dòng giấy tờ đầu lấy từ phiếu weekly gần nhất (≤ 14 ngày) hoặc kho chứng từ.
- Nhắc: `checklist:remind-drivers` chạy 5 phút/lần, đọc `checklist_reminder_slots` (giờ HH:MM, thứ ISO 1–7). Chống gửi trùng bằng cache key slot + ngày + user. `escalate_after_minutes` → 1 bản tổng hợp cho điều phối.

## Hồ sơ chi phí xe

Luồng trạng thái: `draft → submitted → signed → paid`; kế toán có thể `rejected` (tài xế sửa, nộp lại); `cancelled` từ draft/submitted/rejected.

- **Sổ chi phí theo xe = `trip_costs`** (thêm `has_invoice`, `invoice_no`, `seller_name`, `seller_address`, `unit`, `quantity`, `unit_price`).
- DX và TU là số dự kiến → dòng không tạo chi phí. TT, HU, BK → mỗi dòng gắn một `trip_cost`: chọn chi phí đã khai hoặc nhập mới (hệ thống tạo `trip_cost` standalone, `owns_trip_cost = true`).
- Một `trip_cost` không nằm trong 2 hồ sơ **cùng loại** đang hiệu lực (BK con được phép dùng chung với TT/HU cha).
- Nộp TT/HU có dòng không hóa đơn → tự tạo/cập nhật **Bảng kê con** (`parent_id`).
- `submitted/signed/paid` khóa các `trip_costs` liên kết (`FinancialDataLock::tripCostLockedByExpenseDocument`, API sửa/xóa chi phí trả 409).
- `paid` → xác nhận (`confirmed`) các trip_cost còn chờ; bảng kê con chuyển `paid`.
- Hủy hồ sơ → xóa các trip_cost do hồ sơ tạo nếu chưa xác nhận và không nằm trong hồ sơ khác.
- Người đề nghị, đơn vị, người ký: mặc định từ `expense_form_settings`, tài xế sửa được, lưu snapshot vào hồ sơ.
- Duyệt: in ra ký tay → upload bản scan (`signed_scan`) → `signed`.

### File in

- PDF (DomPDF): `resources/views/pdf/expense-*.blade.php`, `checklist-submission.blade.php`.
- XLSX: điền vào template cắt từ file mẫu gốc `resources/templates/expenses/{proposal,payment_request,no_invoice_statement}.xlsx` (giữ logo, merge, định dạng). Nhiều dòng hơn mẫu → chèn dòng, dời vị trí chữ ký. Số tiền bằng chữ: `App\Support\VietnameseMoneyWords`.
- Vị trí người ký trên mẫu theo thứ tự trong `signatories`:
  - DX: [GĐ vận hành, Trưởng đơn vị, Tổ trưởng tài xế, PTGĐ]
  - TT/TU/HU: [Trưởng đơn vị, P. Kế toán, PTGĐ]
  - BK: [PTGĐ]

## Màn hình

| App tài xế | Back-office (`/mng`) |
|---|---|
| `/driver` thẻ "Checklist hôm nay" | `/checklists` ma trận tuân thủ + danh sách |
| `/driver/checklists`, `/driver/checklists/:id` | `/checklists/:id`, `/checklists/settings` |
| `/driver/costs` → lối vào hồ sơ | `/expenses`, `/expenses/:id` |
| `/driver/expenses`, `/new`, `/:id`, `/:id/edit` | `/expenses/ledger` (sổ theo xe), `/expenses/settings` |

## Kiểm thử

- PHPUnit: `tests/Feature/Checklist`, `tests/Feature/Expenses`, `DocumentCodeGeneratorTest`, `tests/Unit/Support/{VietnameseMoneyWords,ChecklistResultCalculator}Test`.
- Playwright: `tests/e2e/driver/driver-checklist.spec.ts`, `driver-expense.spec.ts` (đăng nhập bằng `loginWithToken` — trang `/login` hiện chỉ có Google OAuth).
