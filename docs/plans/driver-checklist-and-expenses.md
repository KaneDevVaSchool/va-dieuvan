# Plan triển khai — Checklist tài xế & Hồ sơ chi phí xe

> Pilot tháng 10/2026. Nguồn dữ liệu: `data/Dữ liệu tham khảo - Phần mềm Điều vận.xlsx`
> (sheet 1–2: Checklist; sheet 3–5: Phiếu đề xuất, Bảng kê không hóa đơn, Đề nghị thanh toán).

> **Trạng thái:** đã triển khai toàn bộ (Phase 0–7). Tài liệu module: [docs/modules/driver-checklist-expenses.md](../modules/driver-checklist-expenses.md).
> Điều chỉnh so với plan: mã hồ sơ cấp ngay khi tạo (để bản in nháp đã có mã); feature toggle mặc định **bật** (toggle hiện là toàn cục, không bật theo nhóm tài xế — tắt trong Quyền hạn → Feature toggle nếu cần).

## 0. Quyết định đã chốt

| # | Chủ đề | Quyết định |
|---|---|---|
| 1 | Daily vs Weekly | **Daily** = bảng 1 sheet "Nội dung Checklist" (dòng 3–40). **Weekly** = bảng 2 (dòng 43–58: giấy tờ, bảo hiểm, đào tạo, phù hiệu). Khi in mẫu "Checklist hằng ngày", mục 1–7 (giấy tờ) lấy từ kết quả weekly gần nhất + hạn giấy tờ trong compliance documents. |
| 2 | Khung giờ nhắc | Admin **tự cấu hình** (giờ, thứ trong tuần, template, bật/tắt, có báo điều phối khi quá hạn hay không). |
| 3 | Tần suất phiếu | **Linh động**: tài xế làm checklist bất kỳ lúc nào, bao nhiêu lần cũng được, mỗi lần chọn xe (tùy chọn gắn chuyến/ngày TP). Kỳ (ngày/tuần) được tính là "đã làm" khi có ≥ 1 phiếu đã nộp. Nhắc nhở bỏ qua tài xế đã hoàn thành kỳ. |
| 4 | Critical = NG | **Không chặn** chuyến. Push cảnh báo ngay cho điều phối + banner cảnh báo trên app tài xế khi bắt đầu chuyến với xe có phiếu NG trong ngày. |
| 5 | Đơn vị / người ký | **Tài xế tự nhập**. Hệ thống điền mặc định từ cấu hình (`expense_form_settings`), tài xế sửa được; lưu snapshot vào hồ sơ. |
| 6 | Duyệt | **In ra ký tay → upload bản scan**. Hệ thống chỉ theo dõi trạng thái. |
| 7 | File in | **Cả PDF và XLSX**. |
| 8 | Mã hồ sơ | `{TIỀN TỐ}-{YYMMDD}-{n}`, `n` bắt đầu từ 1 mỗi ngày, đếm riêng theo tiền tố. Ví dụ `DX-260930-1`. |

Tiền tố:

| Tiền tố | Chứng từ |
|---|---|
| `CL` | Phiếu checklist |
| `DX` | Phiếu đề xuất (Đề nghị mua hàng hóa/dịch vụ – BM.01/MH.QT.01) |
| `TT` | Đề nghị thanh toán (BM03-PKT-QT-TUTT) |
| `TU` | Đề nghị tạm ứng (BM03-PKT-QT-TUTT) |
| `HU` | Đề nghị hoàn ứng (BM03-PKT-QT-TUTT) |
| `BK` | Bảng kê mua vào không có hóa đơn (01/TNDN) |

---

## 1. Hạ tầng dùng chung (Phase 0)

### 1.1 Sinh mã theo ngày
- Migration `document_sequences`: `prefix` (string 8), `seq_date` (date), `last_number` (uint), timestamps; unique `(prefix, seq_date)`.
- `app/Support/DocumentCodeGenerator.php` → `next(string $prefix, ?CarbonInterface $date = null): string`
  - Trong `DB::transaction`: `firstOrCreate` + `lockForUpdate()` rồi tăng `last_number` → không trùng khi nhiều tài xế nộp cùng lúc.
  - Ngày theo `Asia/Ho_Chi_Minh`.
- Test: sinh tuần tự, reset qua ngày mới, đếm riêng theo tiền tố, song song không trùng.

### 1.2 Đọc số tiền bằng chữ
- `app/Support/VietnameseMoneyWords.php` → `160000` → `"Một trăm sáu mươi nghìn đồng."` (khớp output mẫu trong sheet Bảng kê).
- Unit test các ca: 0, lẻ (105), mười/mươi, mốt/một, lăm/năm, tư, nghìn/triệu/tỷ, số âm.

### 1.3 Feature toggle & quyền
- `FeatureToggleSeeder`: `driver_checklist`, `driver_expense` (mặc định tắt, bật cho nhóm pilot).
- `RbacSeeder` thêm quyền:

| Quyền | Role mặc định |
|---|---|
| `checklist.submit` | driver |
| `checklist.view` | dispatcher, admin |
| `checklist.manage` (template, khung giờ) | admin, dispatcher |
| `expense.submit` | driver |
| `expense.view` | dispatcher, accountant, admin |
| `expense.manage` (xác nhận scan, từ chối, đánh dấu đã thanh toán, cấu hình mẫu) | accountant, admin |

- `PermissionDisplayVi` / `PermissionPlainVi`: thêm nhãn tiếng Việt.

---

## 2. Tính năng 1 — Checklist Daily/Weekly

### 2.1 Database

**`checklist_templates`**
- `code` (unique: `daily_safety`, `weekly_compliance`), `name`, `frequency` enum(`daily`,`weekly`), `version` (uint), `is_active`, `form_meta` json (kí hiệu, ngày hiệu lực, ngày ban hành, lần ban hành, lời xác nhận), timestamps, softDeletes.

**`checklist_template_items`**
- `template_id` FK cascade, `group_name`, `sort_order`, `title`, `requirement`, `severity` enum(`critical`,`non_critical`), `ng_action`, `allow_na` bool (các mục "nếu có / nếu áp dụng"), `requires_photo_on_ng` bool (mặc định = critical), `auto_source` nullable (vd `driver_doc:license`, `vehicle_doc:inspection`, `vehicle_doc:insurance_tnds`), `is_active`, timestamps.
- Index `(template_id, is_active, sort_order)`.

**`checklist_reminder_slots`**
- `template_id` FK, `remind_at` (time), `weekdays` json (`[1..7]`), `escalate_after_minutes` nullable (null = không báo điều phối), `is_active`, `created_by`, timestamps.

**`checklist_submissions`**
- `code` (unique, `CL-YYMMDD-n`), `template_id` FK restrict, `template_version`
- `driver_id` FK restrict, `user_id` FK nullOnDelete, `vehicle_id` FK nullOnDelete, `trip_id` nullable, `tp_program_day_id` nullable
- `period_type` (`daily`/`weekly`), `period_key` (`2026-10-05` / `2026-W41`)
- `started_at`, `submitted_at` (**giờ server**, nguồn chính cho "mốc thời gian"), `client_submitted_at` (giờ thiết bị, chỉ để đối chiếu)
- `location_text`, `lat`, `lng` (nullable)
- `trip_info` json (snapshot nhóm "Thông tin chuyến": cơ sở/tuyến, giờ xuất bến, bảo mẫu…)
- `result` enum(`pass`,`fail`) nullable, `critical_ng_count`, `non_critical_ng_count`
- `note`, `inspector_name` (người kiểm tra nếu có), `confirmed_statement_at`
- `status` enum(`draft`,`submitted`,`reviewed`), `reviewed_by`, `reviewed_at`, `review_note`
- `lock_version`, timestamps, softDeletes
- Index: `(status, period_type, period_key)`, `(driver_id, period_key)`, `(vehicle_id, submitted_at)`, `(result, submitted_at)`.

**`checklist_submission_items`**
- `submission_id` FK cascade, `template_item_id` FK nullOnDelete
- Snapshot: `group_name`, `title`, `requirement`, `severity`, `ng_action`, `sort_order`
- `result` enum(`ok`,`ng`,`na`) nullable, `note`, `checked_at`, `prefill_hint` json (vd `{ "source": "vehicle_doc", "expires_at": "2026-12-01", "state": "valid" }`)
- Ảnh minh chứng: `attachments` morph (`kind = checklist_evidence`).
- Unique `(submission_id, template_item_id)`.

**Seeder** `ChecklistTemplateSeeder`: dữ liệu cứng lấy từ sheet 2.
- Daily: 30 mục (An toàn vận hành 17, Kỹ thuật 7, Vệ sinh 3, Hồ sơ hành trình 3).
- Weekly: 16 mục.
- "Thông tin chuyến" không phải mục tick → form header. "Kết luận" không phải mục → tính tự động.
- Slot mặc định (admin sửa lại): daily 05:30 T2–T7, weekly 07:00 T2.

### 2.2 Backend — `app/Services/Checklist/`

| Class | Trách nhiệm |
|---|---|
| `ChecklistTemplateService` | CRUD template/mục; sửa mục → tăng `version`; audit `checklist_template.*` |
| `ChecklistReminderSlotService` | CRUD khung giờ; audit |
| `ChecklistPrefillService` | Điền `trip_info` từ trip/TP day trong ngày của tài xế + xe; gợi ý kết quả cho mục có `auto_source` từ `DriverComplianceDocument`/`VehicleComplianceDocument` (hết hạn → gợi ý NG). Tài xế vẫn phải bấm xác nhận. |
| `ChecklistSubmissionService` | `start()` (sinh mã, snapshot mục, prefill), `saveItems()` (optimistic lock, ghi `checked_at` server), `submit()` (validate đủ mục, NG phải có ghi chú, NG critical phải có ảnh; tính `result`; set `submitted_at = now()`), `review()`. Tất cả trong `DB::transaction` + `AuditLogger` (`checklist.started`, `checklist.submitted`, `checklist.critical_ng`, `checklist.reviewed`). |
| `ChecklistResultCalculator` | Hàm thuần: có mục critical NG → `fail`; tách riêng để unit test. |
| `ChecklistComplianceQuery` | Dashboard: tài xế × kỳ (đã làm / chưa / NG), eager load, lọc theo ngày/tuần/xe/tài xế. |
| `ChecklistDailyPdfPresenter` | Chuẩn dữ liệu cho mẫu in "Phiếu kiểm tra an toàn xe (Daily)" (35 dòng; dòng 1–7 lấy từ weekly gần nhất + hạn giấy tờ). |
| `ChecklistXlsxWriter` | Xuất danh sách/chi tiết phiếu ra Excel (OpenSpout cho danh sách, PhpSpreadsheet cho phiếu theo mẫu). |

**Nhắc nhở & cảnh báo**
- Command `checklist:remind-drivers` → `Kernel` chạy `everyFiveMinutes()->timezone('Asia/Ho_Chi_Minh')`.
  - Lấy slot có `remind_at` rơi vào cửa sổ 5 phút hiện tại và đúng `weekdays`.
  - Đối tượng: tài xế `employment_status = active` có `user_id`, chưa có phiếu `submitted` trong kỳ.
  - Chống gửi trùng: `Cache::add("checklist-remind:{slot}:{date}:{driver}", 1, 1 ngày)`.
  - `escalate_after_minutes` → lần quét sau khi quá hạn gửi `ChecklistOverdueDigestNotification` cho điều phối (1 bản tổng hợp danh sách tài xế chưa làm).
- Notifications (`database` channel → `SendWebPushOnDatabaseNotification` tự đẩy push):
  - `DriverChecklistReminderNotification` (url `/driver/checklists/new?template=...`)
  - `ChecklistCriticalFailedNotification` → người nhận qua `DispatchStaffNotificationRecipients`
  - `ChecklistOverdueDigestNotification`
- Banner cảnh báo: `GET /driver/summary` trả thêm `checklist_today` (trạng thái kỳ + xe có NG) → hiển thị ở dashboard và `DriverTripBottomBar` khi bắt đầu chuyến.

**Routes**

`driver-read.php` / `driver-mutate.php` (middleware `feature:driver_checklist`, FormRequest check `checklist.submit`):
```
GET    /driver/checklists/overview          # kỳ hiện tại daily/weekly + phiếu gần nhất
GET    /driver/checklists                   # lịch sử của tôi
GET    /driver/checklists/{submission}
POST   /driver/checklists                   # start {template_id, vehicle_id, trip_id?, tp_program_day_id?}  (idempotency)
PATCH  /driver/checklists/{submission}/items   # lưu nháp từng phần (lock_version)
POST   /driver/checklists/{submission}/submit
DELETE /driver/checklists/{submission}      # chỉ draft
GET    /driver/checklists/{submission}/pdf
```
`dispatch-staff-read.php` / `-mutate.php`:
```
GET    /checklists/compliance               # checklist.view
GET    /checklists                          # danh sách + filter
GET    /checklists/{submission}
POST   /checklists/{submission}/review
GET    /checklists/{submission}/pdf | /xlsx
GET    /checklists/export.xlsx
CRUD   /checklist-templates, /checklist-templates/{t}/items, /checklist-reminder-slots   # checklist.manage
```

### 2.3 Frontend (`resources/js/src/`)

**Tài xế**
- `views/driver/DriverChecklistsView.vue`: 2 thẻ Daily / Weekly (trạng thái kỳ), nút "Làm checklist", lịch sử.
- `views/driver/DriverChecklistFormView.vue`:
  - Header: chọn xe (`/driver/cost-vehicles`), gắn chuyến (tùy chọn), thông tin chuyến đã prefill (sửa được).
  - Mục gom theo nhóm (accordion), nút lớn **OK / NG / N/A**; NG → hiện "Hành động khi NG", bắt buộc ghi chú; critical → bắt buộc chụp ảnh.
  - Thanh tiến độ cố định, tự lưu nháp (debounce), nộp kèm tick lời xác nhận.
  - Kết quả sau khi nộp: ĐẠT / KHÔNG ĐẠT + danh sách mục NG và hành động.
- `components/driver/checklist/*`: `ChecklistItemRow`, `ChecklistGroupSection`, `ChecklistProgressBar`, `ChecklistResultCard`, `DriverChecklistTodayCard` (gắn vào `DriverDashboard`).
- `composables/useDriverChecklist.js`: load/start/save/submit, xử lý 409 lock_version.
- Router: `/driver/checklists`, `/driver/checklists/:id`; menu trong `LayoutDriver`.

**Điều phối / Admin**
- `views/checklists/ChecklistComplianceView.vue`: lưới tài xế × ngày (tuần), màu theo trạng thái, lọc NG.
- `views/checklists/ChecklistDetailView.vue`: chi tiết, ảnh, review, In PDF / XLSX.
- `views/checklists/ChecklistSettingsView.vue`: template & mục (sắp xếp, severity), khung giờ nhắc.
- Menu: thêm vào `MenuService` theo quyền.

### 2.4 In ấn
- `resources/views/pdf/checklist-daily.blade.php`: dựng lại layout sheet "Checklist hằng ngày" (header trường, 35 dòng, cột Đạt ✓ / Không đạt ✗ / Ghi chú, lời xác nhận có điền biển số, 2 ô ký). Font DejaVu Sans như các PDF hiện có.
- `checklist-weekly.blade.php`: dạng bảng theo sheet 2 (Nhóm, Hạng mục, Yêu cầu, Mức độ, OK/NG, Ghi chú, Giờ kiểm).
- XLSX: `storage/app/templates/checklists/daily.xlsx` (cắt từ sheet 1) → điền bằng PhpSpreadsheet.

---

## 3. Tính năng 2 — Hồ sơ Đề xuất / Thanh toán chi phí xe

### 3.1 Luồng nghiệp vụ

```
Phiếu đề xuất (DX) ──► [Tạm ứng (TU)] ──► phát sinh chi phí (trip_costs theo xe)
                                                  │
                         ┌────────────────────────┴───────────────┐
                 có hóa đơn                                  không hóa đơn
                         │                                        │
                         ▼                                        ▼
        Đề nghị thanh toán (TT) / Hoàn ứng (HU)  ◄── đính kèm ── Bảng kê (BK)
```

Trạng thái hồ sơ:

```
draft ──submit──► submitted ──upload scan──► signed ──mark paid──► paid (khóa)
  ▲                   │                        │
  └──── rejected ◄────┴────────────────────────┘        draft/submitted ──► cancelled
```
- `draft`: tài xế sửa tự do.
- `submitted`: đã chốt số liệu, in ra ký; tài xế không sửa được (muốn sửa → back-office "trả lại" → `rejected` → tài xế chỉnh → nộp lại).
- `signed`: đã upload bản scan có chữ ký (tài xế hoặc back-office upload).
- `paid`: kế toán xác nhận đã chi → khóa hồ sơ + khóa các `trip_costs` liên kết.

### 3.2 Database

**Mở rộng `trip_costs`** (sổ chi phí theo xe, không tạo bảng chi phí mới):
- Thêm `has_invoice` bool default true, `invoice_no`, `seller_name`, `seller_address`, `unit`, `quantity` decimal(12,2), `unit_price` decimal(14,2).
- Bổ sung loại: `insurance`, `inspection`, `repair`, `meal`, `supplies` (cập nhật `VALID_TYPES`, label ở `TripCostListXlsxWriter`, `TripCostReportController`, FE).

**`expense_documents`**
- `code` unique, `doc_type` enum(`proposal`,`payment_request`,`no_invoice_statement`), `payment_kind` enum(`payment`,`advance`,`reimbursement`) nullable
- `parent_id` FK self nullOnDelete (TT/HU → DX hoặc TU; BK → TT/HU)
- `driver_id` FK restrict, `requester_user_id` FK nullOnDelete, `vehicle_id` FK nullOnDelete (xe chính)
- Người đề nghị (snapshot, tài xế sửa được): `requester_name`, `requester_email`, `requester_phone`, `department_label`
- Nội dung: `purpose`, `basis`, `proposed_on`, `needed_by`, `is_urgent`, `urgent_reason`, `allocation_target`, `allocation_area`, `handover_person`
- Tiền: `total_amount` decimal(14,2), `amount_in_words`, `payment_due_on`, `payment_method` enum(`cash`,`transfer`) nullable, `bank_account_no`, `bank_name`, `bank_account_holder`
- `signatories` json (snapshot danh sách chức danh + tên từ cấu hình, tài xế sửa được)
- `status` enum(`draft`,`submitted`,`signed`,`paid`,`rejected`,`cancelled`), `submitted_at`, `signed_at`, `paid_at`, `paid_by`, `rejected_reason`
- `lock_version`, timestamps, softDeletes
- Index: `(status, doc_type, created_at)`, `(driver_id, status)`, `(vehicle_id, doc_type, proposed_on)`.

**`expense_document_lines`**
- `document_id` FK cascade, `sort_order`, `vehicle_id` FK nullOnDelete (mỗi dòng 1 xe — mẫu DX có 3 xe/1 phiếu), `trip_cost_id` FK nullOnDelete
- `category`, `description`, `unit`, `quantity`, `unit_price`, `amount`
- `purchased_on`, `seller_name`, `seller_address` (cho BK), `has_invoice`, `note`, timestamps.

**`expense_form_settings`** (1 dòng, admin sửa)
- `company_name`, `tax_code`, `company_address`, `purchasing_place`, `city_label` ("Tp. Hồ Chí Minh")
- `form_codes` json (BM.01/MH.QT.01, BM03-PKT-QT-TUTT, 01/TNDN, lần ban hành…)
- `default_department_label`, `default_signatories` json theo từng loại chứng từ (chức danh + tên: GĐ vận hành, Trưởng đơn vị, Tổ trưởng tài xế, PTGĐ, P. Kế toán…).
- Seeder lấy giá trị từ file mẫu.

Scan có chữ ký: `attachments` morph trên `ExpenseDocument` (`kind = signed_scan`); ảnh hóa đơn tiếp tục nằm trên `TripCost`.

### 3.3 Backend — `app/Services/Expenses/`

| Class | Trách nhiệm |
|---|---|
| `ExpenseDocumentService` | `createDraft`, `updateDraft`, `submit` (chốt tổng, `amount_in_words`, sinh mã theo ngày nộp), `uploadSignedScan`, `reject`, `markPaid`, `cancel`. Transaction + audit (`expense.created/submitted/signed/rejected/paid/cancelled`) + optimistic lock. |
| `ExpenseLineService` | Thêm/sửa/xóa dòng; với TT/HU/BK: chọn `trip_costs` có sẵn **hoặc** nhập mới → tự tạo `trip_cost` standalone gắn `vehicle_id` (để sổ chi phí xe luôn đầy đủ). DX không tạo `trip_cost` (chưa chi). Chặn 1 `trip_cost` nằm trong 2 hồ sơ TT/HU chưa hủy. |
| `NoInvoiceStatementBuilder` | Từ TT/HU: gom dòng `has_invoice = false` → tạo/cập nhật BK con. |
| `ExpenseDocumentAccess` (Support) | Tài xế chỉ thấy hồ sơ của mình; back-office theo quyền. |
| `FinancialDataLock` | Thêm `assertExpenseDocumentEditable()`, `assertTripCostNotInPaidExpense()`; gọi trong `TripCostController` update/delete hiện có. |
| `VehicleCostLedgerQuery` | Sổ chi phí theo xe: tổng theo tháng/loại, có/không hóa đơn, trạng thái thanh toán. |
| `Export/ExpenseProposalPdf`, `PaymentRequestPdf`, `NoInvoiceStatementPdf` | Blade + DomPDF. |
| `Export/ExpenseDocumentXlsxWriter` | Mở template `storage/app/templates/expenses/{proposal,payment_request,no_invoice_statement}.xlsx` (cắt từ sheet 3–5), điền ô, chèn thêm dòng khi vượt số dòng mẫu, giữ merge/format. |
| `VehicleCostLedgerXlsxWriter` | Xuất sổ chi phí xe. |

**Routes**

Tài xế (`feature:driver_expense`, `expense.submit`):
```
GET    /driver/expenses                      # của tôi, filter type/status
GET    /driver/expenses/defaults             # đơn vị, người ký mặc định, thông tin tài xế
GET    /driver/expenses/{doc}
POST   /driver/expenses                      # idempotency
PATCH  /driver/expenses/{doc}
PUT    /driver/expenses/{doc}/lines
POST   /driver/expenses/{doc}/submit
POST   /driver/expenses/{doc}/signed-scan
POST   /driver/expenses/{doc}/cancel
GET    /driver/expenses/{doc}/pdf | /xlsx
GET    /driver/expense-cost-candidates?vehicle_id=  # trip_costs chưa vào hồ sơ nào
```
Back-office:
```
GET    /expenses                             # expense.view
GET    /expenses/{doc}  | /pdf | /xlsx
POST   /expenses/{doc}/signed-scan | /reject | /mark-paid   # expense.manage
GET    /vehicles/{vehicle}/cost-ledger | /cost-ledger.xlsx   # expense.view
GET/PUT /expense-form-settings                              # expense.manage
```

### 3.4 Frontend

**Tài xế**
- `views/driver/DriverExpensesView.vue`: tab theo trạng thái, thẻ hồ sơ (mã, loại, xe, tổng tiền).
- `views/driver/DriverExpenseEditorView.vue` (wizard 4 bước):
  1. Loại chứng từ (DX / TT / TU / HU / BK) + căn cứ (chọn DX/TU cha nếu có)
  2. Thông tin chung: người đề nghị, đơn vị, mục đích, ngày, gấp, STK… (điền sẵn, sửa được)
  3. Dòng chi phí: chọn xe từng dòng, nhập tay hoặc "Lấy từ chi phí đã khai"; tổng tự tính
  4. Xem trước bản in (PDF inline) → Nộp → Tải PDF/XLSX
- `views/driver/DriverExpenseDetailView.vue`: trạng thái, tải file, upload scan có chữ ký, lý do bị trả lại.
- `components/driver/expenses/*`, `composables/useDriverExpense.js`.
- Form chi phí hiện có (`DriverCostCreateView`): thêm trường có hóa đơn / người bán / địa chỉ / SL / đơn giá.

**Back-office**
- `views/expenses/ExpenseListView.vue` (lọc xe/loại/trạng thái/tháng/tài xế), `ExpenseDetailView.vue` (trả lại, xác nhận scan, đánh dấu đã thanh toán, tải file).
- `views/resources/VehicleCostLedgerView.vue` (hoặc tab trong chi tiết xe): sổ chi phí từng xe + xuất Excel.
- `views/system/ExpenseFormSettingsView.vue`.

---

## 4. Kiểm thử

**PHPUnit — Unit**
- `VietnameseMoneyWordsTest`, `DocumentCodeGeneratorTest`, `ChecklistResultCalculatorTest`.

**PHPUnit — Feature**
- Checklist: tài xế tạo/nộp; NG thiếu ghi chú/ảnh bị từ chối; critical NG → notification điều phối; nhiều phiếu/ngày; tài xế không xem phiếu người khác; command nhắc chỉ gửi người chưa làm, không gửi trùng, đúng thứ trong tuần; feature toggle tắt → 404/403.
- Expense: sinh mã `DX-YYMMDD-n` tăng dần và reset qua ngày; tạo TT từ trip_costs; 1 trip_cost không vào 2 hồ sơ; BK gom đúng dòng không hóa đơn; submitted không sửa được; paid khóa hồ sơ + trip_costs (mở rộng `FinancialLockTest`); PDF/XLSX trả 200 đúng content-type; quyền `expense.manage`.

**Playwright E2E** (`tests/e2e/`)
- `driver-checklist.spec`: làm daily từ đầu tới nộp, có 1 mục NG.
- `driver-expense.spec`: tạo DX → TT tham chiếu DX → tải PDF.

---

## 5. Lộ trình

| Phase | Thời gian | Nội dung | Kết quả bàn giao |
|---|---|---|---|
| 0 | 01–02/10 | Mã theo ngày, đọc số tiền bằng chữ, feature toggle, quyền, cắt template XLSX | Hạ tầng + unit test |
| 1 | 03–07/10 | Checklist: migration, seeder từ Excel, services, API, feature test | API checklist chạy được |
| 2 | 08–10/10 | Checklist: UI tài xế, prefill, push nhắc, cảnh báo NG | Tài xế dùng được trên app |
| 3 | 11–13/10 | Checklist: dashboard điều phối, cấu hình slot/template, PDF/XLSX | **Bật pilot checklist** (1 nhóm tài xế) |
| 4 | 14–18/10 | Chi phí: mở rộng trip_costs, expense documents, lines, khóa tài chính, feature test | API hồ sơ chạy được |
| 5 | 19–23/10 | Chi phí: wizard tài xế, 3 mẫu PDF + XLSX, upload scan | Tài xế tạo & in hồ sơ |
| 6 | 24–27/10 | Back-office, sổ chi phí theo xe, cấu hình mẫu | **Bật pilot hồ sơ chi phí** |
| 7 | 28–31/10 | E2E, cập nhật docs (`docs/business/rules.md`, `docs/api/endpoints.md`, `docs/database/schema.md`, `docs/modules/`), thu phản hồi pilot | Tổng kết pilot |

Mỗi phase là 1 nhánh/PR riêng, chạy `./vendor/bin/pint`, `php artisan test`, `npm run lint` trước khi merge.

## 6. Rủi ro & lưu ý

- **Giờ thiết bị sai**: mọi mốc thời gian nghiệp vụ lấy giờ server; giờ client chỉ lưu tham chiếu.
- **Mạng yếu khi kiểm tra xe**: tự lưu nháp từng phần; nộp dùng idempotency key để bấm lại không tạo trùng.
- **DomPDF không dựng hoàn hảo merge phức tạp**: PDF dựng lại bằng HTML/CSS (không convert từ XLSX); XLSX điền vào template gốc để giữ đúng mẫu.
- **Sửa template checklist khi đang pilot**: snapshot mục vào phiếu + `template_version` nên lịch sử không đổi.
- **Push không tới** (chưa cấp quyền thông báo): thẻ "Checklist hôm nay" trên dashboard + digest quá hạn cho điều phối là lớp dự phòng.
