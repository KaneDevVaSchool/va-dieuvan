# 🚀 VA SCHOOLS — DEV TOOLKIT
## Phần mềm Điều vận | Sprint Post Demo 02 | v1.0 | 14/05/2026

> **Tài liệu này được tổng hợp từ:** Dev Brief Sprint Post Demo 02 + SRS v1.3 + Biên bản họp Demo 02 (09/05/2026) + Recap email Điều vận (11/05/2026)
> 
> **Áp dụng:** `@.cursorrules` | `@.cursor/` | `@.cursorignore`

---

# PHẦN 1 — ANALYSIS

## 1.1 Tổng quan hệ thống

| Thông tin | Chi tiết |
|---|---|
| Hệ thống | Phần mềm Điều vận VA Schools |
| SRS Version | v1.3 (cập nhật 11/05/2026) |
| Sprint | Post Demo 02 |
| Lead Review | Nguyễn Anh Khoa |
| Trạng thái | Ready to develop (trừ Task 3.4 — cần grooming) |

## 1.2 Business Flow phân tích

### Luồng duyệt phiếu 5 bước (CORE FLOW)
```
Người đề xuất → Tạo phiếu (không thấy giá)
     ↓
NV Điều vận → Tiếp nhận, fill đơn giá → [Chờ Trưởng đơn vị duyệt]
     ↓
Trưởng đơn vị → Nhận push notification → Duyệt / Từ chối
     ↓ (Duyệt)
4A: Người đề xuất → Xuất PDF → In → Trình ký giấy
4B: Điều vận → Điều phối xe + tài xế (song song)
     ↓
Người đề xuất → Upload scan phiếu đã ký → [Lưu trữ hoàn tất]
```

### Luồng Recurring (P2P Ngoại khóa)
```
Tạo phiếu 1 lần → Cấu hình recurring (ngày/tuần/tháng)
     ↓
Hệ thống auto-generate danh sách chuyến
     ↓
Mỗi tuần: Người đề xuất cập nhật sĩ số (deadline T-24h)
     ↓
Điều vận override nếu khẩn
```

## 1.3 User Roles & Permissions

| Role | Tạo phiếu | Xem giá | Fill giá | Duyệt | Xuất PDF | Điều phối | Upload scan |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Người đề xuất | ✅ | Read-only (sau B2) | ❌ | ❌ | ✅ (sau duyệt) | ❌ | ✅ |
| NV Điều vận | ❌ | ✅ Edit | ✅ | ❌ | ✅ | ✅ | ❌ |
| Trưởng đơn vị | ❌ | Read-only | ❌ | ✅ (phòng mình) | ❌ | ❌ | ❌ |
| Admin | ✅ | ✅ | ✅ | Config | ✅ | ✅ | ✅ |

## 1.4 Module Breakdown

| Module | Status | Priority |
|---|---|---|
| Phiếu đề xuất (Xe Công Tác, P2P Ngoại khóa, Hàng Hóa) | Existing + Update | P0 |
| Luồng duyệt 5 bước | New/Refactor | P0 |
| Phân quyền xuất PDF | New | P0 |
| Recurring chuyến định kỳ | New | P1 |
| Cập nhật sĩ số theo chuyến | New | P1 |
| Hồ sơ xe-tài xế NCC | New | P1 |
| Link Bảng giá tham chiếu | New (UX) | P2 |
| P2P Học sinh chính sách | New (Roadmap) | P3 |
| Mobile App — Trưởng đơn vị duyệt | New | P0 |
| Upload scan phiếu đã ký | New | P1 |
| Settings — Admin URL config | New | P2 |

## 1.5 Edge Cases phát hiện

- Trưởng đơn vị từ chối → Người đề xuất phải sửa và resubmit → luồng 5 bước lặp lại
- Cập nhật sĩ số khi còn < 24h → cần error message rõ ràng + contact Điều vận
- Admin chưa config URL Bảng giá → link tự ẩn, không lỗi
- Phiếu cũ trong DB (có field "Dùng cho 3+ ngày") → cần migrate gracefully
- Recurring + vượt ngưỡng chi phí → chỉ cảnh báo, không block
- Clone phiếu (Đặt lại) → phiếu mới đi qua đủ 5 bước, không bỏ qua approval
- Push notification Trưởng đơn vị → scoped theo phòng, không cross-department

---

# PHẦN 2 — CHECKLISTS

---

## ✅ CHECKLIST 1 — PHÂN TÍCH HỆ THỐNG

### 1A. Business Flow
- [ ] Đã map đầy đủ luồng 5 bước (tạo → fill giá → duyệt → PDF/điều phối → upload scan)
- [ ] Đã xác định điểm rẽ nhánh: Duyệt vs Từ chối
- [ ] Đã xác định luồng Resubmit sau khi bị từ chối
- [ ] Đã xác định luồng Recurring (tạo 1 lần → auto-generate nhiều chuyến)
- [ ] Đã xác định luồng Clone phiếu (Đặt lại)
- [ ] Đã xác định luồng P2P Học sinh chính sách (auto-generate theo khung giờ cố định)
- [ ] Song song track: 4A (xuất PDF) và 4B (điều phối) được xử lý độc lập

### 1B. User Role & Permission
- [ ] Ba role chính đã rõ: Người đề xuất / NV Điều vận / Trưởng đơn vị
- [ ] Trưởng đơn vị chỉ duyệt phiếu phòng mình (phân quyền theo department)
- [ ] Trường giá: ẩn hoặc disabled với Người đề xuất khi tạo phiếu
- [ ] Người đề xuất xem được giá sau khi Điều vận fill (Bước 2 — read-only)
- [ ] NV Điều vận có quyền override sĩ số học sinh bất cứ lúc nào
- [ ] Admin có quyền cấu hình URL Bảng giá trong Settings
- [ ] Phân quyền upload scan: chỉ Người đề xuất

### 1C. Module & CRUD
- [ ] Phiếu đề xuất: Create, Read, Update (giá — Điều vận), Status transitions
- [ ] Recurring: Create config, Read danh sách chuyến, Update sĩ số từng chuyến
- [ ] Hồ sơ xe-tài xế NCC: Full CRUD
- [ ] Attachment (upload scan): Create, Read
- [ ] Settings (URL config): Read, Update (Admin only)
- [ ] Push notification: trigger events đã xác định

### 1D. Validation Rules
- [ ] Từ chối phiếu: bắt buộc nhập lý do (không để trống)
- [ ] Upload scan: chỉ PDF/JPG/PNG, tối đa 10MB
- [ ] Cập nhật sĩ số: chỉ khi còn > 24h trước giờ khởi hành
- [ ] Recurring: Ngày kết thúc phải sau ngày bắt đầu
- [ ] Giá dịch vụ: numeric, không âm
- [ ] Label "Trưởng đoàn phụ trách": không đổi field name trong DB/API, chỉ đổi label UI

### 1E. Workflow & Status Machine

```
[Draft] → [Chờ Điều vận fill giá] → [Chờ Trưởng đơn vị duyệt]
                                              ↓              ↓
                                         [Đã duyệt]    [Từ chối]
                                              ↓              ↓
                                   [In PDF + Điều phối]  [Sửa → Resubmit]
                                              ↓
                                   [Lưu trữ hoàn tất]
```

- [ ] Đã define đầy đủ trạng thái: Draft, Chờ fill giá, Chờ duyệt, Đã duyệt, Từ chối, Lưu trữ hoàn tất
- [ ] Transition rules không cho phép skip bước
- [ ] Status history được log (ai thay đổi, lúc nào)

### 1F. Notification
- [ ] Trigger: Điều vận fill giá xong → push notification Trưởng đơn vị
- [ ] Trigger: Trưởng đơn vị duyệt → notification Người đề xuất + Điều vận
- [ ] Trigger: Trưởng đơn vị từ chối → notification Người đề xuất (có lý do)
- [ ] Push notification scoped theo phòng (Trưởng đơn vị không nhận phiếu phòng khác)
- [ ] Notification realtime sau khi duyệt/từ chối
- [ ] Cảnh báo vượt ngưỡng chi phí gói Recurring → notification Người đề xuất + Điều vận

### 1G. API Mapping
- [ ] `POST /phieu` — tạo phiếu mới
- [ ] `PATCH /phieu/{id}/gia` — Điều vận fill giá (restricted role)
- [ ] `PATCH /phieu/{id}/duyet` — Trưởng đơn vị duyệt (restricted role + department)
- [ ] `PATCH /phieu/{id}/tu-choi` — Từ chối (required: reason)
- [ ] `GET /phieu/{id}/pdf` — xuất PDF (restricted: chỉ sau approved)
- [ ] `POST /phieu/{id}/attachment` — upload scan (restricted: Người đề xuất)
- [ ] `POST /phieu/recurring` — tạo chuỗi recurring
- [ ] `PATCH /chuyen/{id}/si-so` — cập nhật sĩ số (24h rule)
- [ ] `GET /settings/bang-gia-url` — lấy URL tham chiếu
- [ ] `PUT /settings/bang-gia-url` — Admin cập nhật URL
- [ ] CRUD `/ho-so-xe`, `/tai-xe`, `/ncc`

### 1H. Edge Cases
- [ ] Phiếu bị từ chối nhiều lần liên tiếp — vẫn hoạt động bình thường
- [ ] Điều vận fill giá khi phiếu đang ở trạng thái không hợp lệ → error 403/409
- [ ] Cùng lúc nhiều Trưởng đơn vị approve cùng phiếu → prevent race condition
- [ ] Upload file > 10MB → rõ ràng error message
- [ ] URL Bảng giá bị broken → không crash, chỉ mở tab với URL đó
- [ ] Recurring: ngày lặp trùng ngày nghỉ lễ → TBD (cần grooming Task 3.4)
- [ ] Clone phiếu của phiếu đang ở trạng thái Draft → có cho phép không?

### 1I. Responsive & Platforms
- [ ] Web app: Desktop (min 1280px) + Tablet (768px)
- [ ] Mobile App: Trưởng đơn vị duyệt, Tài xế check-in học sinh
- [ ] PDF export: A4, đúng layout khi in

### 1J. Security
- [ ] Role-based access control (RBAC) cho tất cả API endpoints
- [ ] Department-scoped data: Trưởng đơn vị chỉ thấy phiếu phòng mình
- [ ] File upload: validate MIME type server-side (không chỉ extension)
- [ ] File upload: scan malware trước khi lưu (nếu applicable)
- [ ] Giá dịch vụ: audit log mỗi lần thay đổi (ai fill, giờ nào)
- [ ] PDF URL: không public, cần authenticated access

### 1K. Performance
- [ ] Danh sách phiếu: pagination + lazy load
- [ ] Recurring: sinh nhiều chuyến → async job, không block UI
- [ ] Push notification: queue-based, không sync call
- [ ] File upload: presigned URL nếu dùng S3/cloud storage

---

## ✅ CHECKLIST 2 — UI/UX REVIEW

### 2A. Layout Consistency
- [ ] Header/Sidebar/Breadcrumb nhất quán trên tất cả màn hình
- [ ] Grid layout đồng nhất (spacing 4px base grid)
- [ ] Section "Cập nhật chi phí dịch vụ" có visual separator rõ ràng với phần thông tin đề xuất
- [ ] Link "Xem Bảng giá tham chiếu" aligned với trường nhập đơn giá, không lệch layout
- [ ] Nút [Duyệt] và [Từ chối] có visual hierarchy rõ ràng (primary vs destructive)

### 2B. Typography
- [ ] Label "Trưởng đoàn phụ trách" đã đổi đúng ở tất cả nơi (form, detail, PDF, filter)
- [ ] Font size labels vs values nhất quán
- [ ] Lý do từ chối: text area đủ rộng để nhập, không bị overflow
- [ ] Cảnh báo vượt ngưỡng chi phí: typography đủ nổi bật (warning color)

### 2C. Color System
- [ ] Status badges nhất quán: Draft (gray), Chờ duyệt (blue/yellow), Đã duyệt (green), Từ chối (red), Lưu trữ (purple)
- [ ] Nút PDF disabled: rõ ràng trạng thái disabled (opacity + cursor: not-allowed)
- [ ] Tooltip trên nút disabled: màu sắc tooltip không xung đột với background
- [ ] Cảnh báo vượt ngưỡng: warning orange/yellow, không dùng red (không block flow)

### 2D. Component Consistency
- [ ] Toggle "Đề xuất định kỳ" dùng cùng component Toggle đang dùng trong hệ thống
- [ ] Checkbox "Ngày trong tuần" (Mon–Sun) dùng cùng Checkbox component
- [ ] Date picker cho Ngày bắt đầu/kết thúc dùng cùng DatePicker component
- [ ] Section nhập giá: dùng cùng Input component, không tự custom riêng
- [ ] Confirmation dialog trước khi duyệt: dùng cùng Dialog/Modal component

### 2E. Responsive — Desktop/Tablet/Mobile
- [ ] **Desktop (≥1280px):** Section nhập giá 2 cột (label left, input right)
- [ ] **Tablet (768–1279px):** Stacked layout, không overflow
- [ ] **Mobile App (Trưởng đơn vị):** Nút [Duyệt] và [Từ chối] đủ to (min 44px touch target)
- [ ] **Mobile App:** Popup nhập lý do từ chối: keyboard không che mất nút Submit
- [ ] **Mobile App:** Push notification deep-link thẳng vào màn hình chi tiết phiếu
- [ ] **PDF export:** Layout chuẩn A4, không bị cắt khi in

### 2F. Empty State
- [ ] Màn hình danh sách phiếu khi chưa có phiếu nào: hiển thị empty state + CTA tạo phiếu
- [ ] Danh sách chuyến Recurring khi chưa có chuyến: empty state rõ ràng
- [ ] Attachment section khi chưa có file: empty state + hướng dẫn upload
- [ ] Hồ sơ xe-tài xế NCC khi chưa có dữ liệu: empty state + CTA thêm mới

### 2G. Loading State
- [ ] Submit duyệt/từ chối: button loading state (spinner), không cho click 2 lần
- [ ] Upload file: progress bar hoặc spinner + phần trăm
- [ ] Generate chuyến Recurring: loading indicator (async job)
- [ ] Export PDF: loading state + thông báo đang xử lý
- [ ] Danh sách phiếu: skeleton loader khi fetch

### 2H. Error State
- [ ] Từ chối không nhập lý do: inline error message dưới text area
- [ ] Upload file sai định dạng: error message rõ loại file được phép
- [ ] Upload file > 10MB: error message kèm kích thước tối đa
- [ ] Cập nhật sĩ số < 24h: error message + hướng dẫn liên hệ Điều vận
- [ ] Network error khi duyệt: retry button + error message
- [ ] API 403 khi truy cập không có quyền: redirect + thông báo phù hợp

### 2I. Table UX
- [ ] Danh sách phiếu: có sort theo ngày tạo, trạng thái
- [ ] Danh sách chuyến Recurring: filter theo tuần/tháng
- [ ] Cột trạng thái: badge/pill dễ nhận biết, không chỉ text thuần
- [ ] Action column: không bị cắt trên màn hình nhỏ
- [ ] Pagination hoặc infinite scroll nhất quán

### 2J. Form UX
- [ ] Trường giá: disabled/hidden rõ ràng với Người đề xuất (không nhập được)
- [ ] Recurring config fields: chỉ hiện khi toggle bật (không chiếm layout khi tắt)
- [ ] Date picker: ngày kết thúc không cho chọn trước ngày bắt đầu
- [ ] Form validation: inline error ngay dưới field, không chỉ toast
- [ ] Required fields: đánh dấu (*) nhất quán

### 2K. Modal/Drawer UX
- [ ] Confirmation dialog duyệt: tóm tắt thông tin phiếu, không chỉ "Bạn có chắc?"
- [ ] Modal lý do từ chối: auto-focus vào textarea, char limit counter nếu có
- [ ] Modal có backdrop click để đóng (trừ modal confirmation quan trọng)
- [ ] Drawer chi tiết phiếu: scroll nội dung bên trong, không scroll cả trang

### 2L. Accessibility cơ bản
- [ ] Nút disabled có `aria-disabled` + tooltip giải thích lý do
- [ ] Toast notifications có đủ thời gian hiển thị (min 3s)
- [ ] Màu sắc status không chỉ dựa vào màu (có icon hoặc text kèm)
- [ ] Form labels liên kết đúng với input (`for`/`htmlFor`)
- [ ] Keyboard navigation hoạt động trên dialog/modal

### 2M. UX Flow thực tế người vận hành

**Kịch bản NV Điều vận:**
- [ ] Vào danh sách → lọc nhanh "Chờ fill giá" → bấm vào 1 phiếu
- [ ] Nhìn thấy ngay section nhập giá (không phải scroll xuống cuối)
- [ ] Bấm link "Xem Bảng giá" → mở tab mới → quay lại nhập giá → Lưu
- [ ] Confirm phiếu đã chuyển sang "Chờ Trưởng đơn vị duyệt"
- [ ] Không bị mất form nếu vô tình reload (autosave draft hoặc confirm trước khi rời)

**Kịch bản Trưởng đơn vị:**
- [ ] Nhận push notification → tap → mở thẳng màn hình chi tiết phiếu
- [ ] Xem đủ thông tin + giá trên 1 màn hình (không phải tab)
- [ ] Bấm Duyệt → Confirmation → Done
- [ ] Bấm Từ chối → Popup lý do → Nhập → Done

---

## ✅ CHECKLIST 3 — CODING

### 3A. Folder Structure
```
src/
├── modules/
│   ├── phieu/           # Phiếu đề xuất (core)
│   ├── recurring/       # Recurring + sĩ số
│   ├── ho-so/          # Hồ sơ xe + tài xế NCC
│   ├── approval/        # Luồng duyệt 5 bước
│   ├── notification/    # Push notification
│   ├── settings/        # Admin settings
│   └── attachment/      # Upload scan phiếu
├── shared/
│   ├── components/      # Reusable UI components
│   ├── hooks/
│   ├── utils/
│   └── constants/
└── api/                 # API service layer
```
- [ ] Không để component logic trong file route/page
- [ ] Mỗi module có folder riêng: `components/`, `hooks/`, `services/`, `types/`
- [ ] Shared components tách riêng, không duplicate

### 3B. Component Reusable
- [ ] `<StatusBadge status={...} />` dùng chung cho tất cả trạng thái phiếu
- [ ] `<ConfirmDialog />` generic, dùng cho cả Duyệt và các action khác
- [ ] `<FileUpload accept="..." maxSize={...} />` dùng chung
- [ ] `<RecurringConfig />` tách riêng, không inline trong form tạo phiếu
- [ ] `<PriceSection />` tái sử dụng được cho các loại phiếu khác nhau

### 3C. Naming Convention
- [ ] Components: PascalCase (`PhieuDetailModal`, `RecurringToggle`)
- [ ] Hooks: `use` prefix (`usePhieuApproval`, `useRecurringConfig`)
- [ ] API services: camelCase noun+verb (`phieuService.approve()`)
- [ ] Constants: SCREAMING_SNAKE (`PHIEU_STATUS`, `MAX_UPLOAD_SIZE_MB`)
- [ ] Không dùng tên viết tắt khó hiểu (`pvh`, `dlv` → `phieuVanHanh`, `dieuVan`)

### 3D. Clean Code
- [ ] Không function > 50 lines (extract thành helpers)
- [ ] Không nested if > 3 levels (early return pattern)
- [ ] Không magic numbers (dùng constants: `DEADLINE_HOURS = 24`)
- [ ] Mỗi file có 1 responsibility rõ ràng
- [ ] Comment khi logic không self-explanatory (đặc biệt business rules)

### 3E. Tránh Hardcode
- [ ] URL Bảng giá: fetch từ Settings API, không hardcode string
- [ ] Deadline 24h: constant `STUDENT_COUNT_UPDATE_DEADLINE_HOURS = 24`
- [ ] File upload limits: constant `UPLOAD_MAX_SIZE_BYTES = 10 * 1024 * 1024`
- [ ] Accepted file types: constant `ALLOWED_ATTACHMENT_TYPES = ['pdf', 'jpg', 'png']`
- [ ] Status labels: i18n-ready hoặc constant map
- [ ] Department IDs: không hardcode, fetch từ API

### 3F. API Separation
- [ ] Tất cả API calls trong `services/` layer, không gọi axios/fetch trực tiếp trong component
- [ ] API error codes được handle centrally (interceptor)
- [ ] Response types được define rõ ràng (TypeScript interfaces)
- [ ] Retry logic cho transient errors (network timeout)
- [ ] Optimistic update + rollback khi API fail (nếu applicable)

### 3G. State Management
- [ ] Phân biệt: server state (React Query/SWR) vs client state (useState/Zustand)
- [ ] Không store server data trong Redux/Zustand khi không cần
- [ ] Phiếu detail: cache + invalidate khi approve/reject
- [ ] Recurring chuyến list: paginated query
- [ ] Notification unread count: global state, sync với push notification

### 3H. Error Handling
- [ ] Try-catch cho tất cả async operations
- [ ] User-friendly error messages (không expose stack trace)
- [ ] 403 Forbidden: redirect về màn hình phù hợp + thông báo rõ
- [ ] 409 Conflict (race condition duyệt phiếu): thông báo + refresh data
- [ ] Upload error: specific message per error type
- [ ] Network offline: graceful degradation + retry option

### 3I. Validation
- [ ] Validate client-side trước khi gọi API (UX nhanh hơn)
- [ ] Validate server-side bắt buộc (không tin tưởng client)
- [ ] Lý do từ chối: min 10 chars, max 500 chars (ví dụ)
- [ ] Giá dịch vụ: số nguyên dương, không được âm
- [ ] Recurring date range: end > start, không quá 1 năm (business rule)
- [ ] Sĩ số học sinh: số nguyên dương, không vượt capacity xe

### 3J. Performance Optimization
- [ ] Danh sách phiếu: pagination (không fetch tất cả)
- [ ] Recurring generate: background job (không block main thread)
- [ ] PDF export: async generation + polling hoặc SSE
- [ ] Image/file preview: lazy load
- [ ] Debounce search/filter inputs (300ms)
- [ ] Memoize components nặng (`React.memo`, `useMemo`)

### 3K. Maintainability
- [ ] Types/interfaces đầy đủ, không dùng `any`
- [ ] Unit tests cho business logic (approval flow, 24h validation, recurring calc)
- [ ] Integration tests cho happy path các luồng chính
- [ ] JSDoc cho shared utilities phức tạp
- [ ] Changelog hoặc commit message convention (feat/fix/refactor)

### 3L. Scalability
- [ ] Permission check abstracted vào hook `usePermission('approve_phieu')`
- [ ] Status machine có thể extend thêm bước mà không refactor toàn bộ
- [ ] Recurring engine tách biệt logic, dễ support weekly/monthly/custom
- [ ] Notification system dùng event-driven pattern

---

# PHẦN 3 — PLAN STRUCTURE (plan.md)

---

# plan.md — VA Schools Điều Vận System
## Sprint Post Demo 02 | Start: 14/05/2026

---

## 📋 Project Overview

Phần mềm Điều vận VA Schools là hệ thống quản lý luồng đề xuất và phê duyệt vận chuyển nội bộ cho hệ thống trường Việt Mỹ. Sprint này tập trung hoàn thiện luồng nghiệp vụ cốt lõi và bổ sung tính năng mới sau kết quả Demo 02.

**Tech Stack:** [Điền vào theo dự án — Frontend / Backend / Mobile / DB]
**SRS Reference:** v1.3 (11/05/2026)
**Lead Dev:** [Tên]
**Lead Review:** Nguyễn Anh Khoa

---

## 🎯 Goals

1. Chuẩn hóa luồng duyệt 5 bước theo quy trình nghiệp vụ thực tế
2. Kiểm soát phân quyền xuất PDF — chỉ sau khi được duyệt
3. Hỗ trợ recurring cho chuyến định kỳ P2P Ngoại khóa
4. Cải thiện UX cho NV Điều vận khi fill chi phí
5. Xây dựng hồ sơ quản lý xe-tài xế NCC
6. Giao diện nhất quán, production-ready, không regression

---

## 📦 Scope

### In Scope (Sprint này)
- Task 1.1 — Đổi label "Trưởng đoàn phụ trách" ✅ (đã hoàn thành)
- Task 1.2 — Xóa trường "Dùng cho 3+ ngày" ✅ (đã hoàn thành)
- Task 2.1 — Luồng duyệt 5 bước + phân quyền PDF
- Task 3.1 — Recurring + cập nhật sĩ số
- Task 3.2 — Link Bảng giá tham chiếu
- Task 3.3 — Hồ sơ xe-tài xế NCC

### Out of Scope (Sprint này)
- Task 3.4 — P2P Học sinh chính sách (cần grooming)
- Chữ ký số Trưởng đơn vị (Giai đoạn 2)
- Tích hợp accounting/finance

---

## 🗂️ Module Breakdown

### Module 1: Approval Flow (Task 2.1)
**Owner:** Senior Dev  
**Estimate:** 3–5 ngày  
**Dependencies:** None (blocking nhất)

Sub-tasks:
- [ ] 2.1.1 — State machine: thêm states mới, transition rules
- [ ] 2.1.2 — API: endpoint duyệt, từ chối, fill giá (phân quyền)
- [ ] 2.1.3 — UI: section nhập giá trên màn hình Điều vận
- [ ] 2.1.4 — UI: nút PDF disabled + tooltip
- [ ] 2.1.5 — Mobile: màn hình duyệt cho Trưởng đơn vị
- [ ] 2.1.6 — Push notification: triggers + routing
- [ ] 2.1.7 — Upload scan phiếu đã ký (Bước 5)
- [ ] 2.1.8 — Test: full flow 5 bước + edge cases

### Module 2: Recurring (Task 3.1)
**Owner:** Mid/Senior Dev  
**Estimate:** 4–6 ngày  
**Dependencies:** Module 1 (phiếu đã có approval flow)

Sub-tasks:
- [ ] 3.1.1 — UI: toggle + config form trong tạo phiếu P2P Ngoại khóa
- [ ] 3.1.2 — Engine: generate danh sách chuyến từ recurring config
- [ ] 3.1.3 — UI: danh sách chuyến recurring (Điều vận view)
- [ ] 3.1.4 — Logic: cập nhật sĩ số + 24h deadline rule
- [ ] 3.1.5 — Logic: cảnh báo vượt ngưỡng chi phí gói
- [ ] 3.1.6 — Feature: "Đặt lại" (Clone phiếu)
- [ ] 3.1.7 — Test: recurring generation + sĩ số + cảnh báo

### Module 3: Hồ sơ xe-tài xế NCC (Task 3.3)
**Owner:** Mid Dev  
**Estimate:** 2–3 ngày  
**Dependencies:** None

Sub-tasks:
- [ ] 3.3.1 — Data model: Xe, Tài xế, NCC (nhà cung cấp)
- [ ] 3.3.2 — API: CRUD endpoints
- [ ] 3.3.3 — UI: danh sách + form tạo/sửa Xe
- [ ] 3.3.4 — UI: danh sách + form tạo/sửa Tài xế
- [ ] 3.3.5 — UI: liên kết Tài xế ↔ Xe ↔ NCC
- [ ] 3.3.6 — Test: CRUD operations

### Module 4: Link Bảng giá (Task 3.2)
**Owner:** Junior/Mid Dev  
**Estimate:** 0.5 ngày  
**Dependencies:** Module 1 (section nhập giá)

Sub-tasks:
- [ ] 3.2.1 — Settings: Admin config URL
- [ ] 3.2.2 — UI: hiển thị link cạnh trường nhập giá
- [ ] 3.2.3 — Logic: ẩn link khi URL chưa config
- [ ] 3.2.4 — Test: config + display + fallback

---

## ⚡ Priority & Sequencing

```
Week 1:
Day 1–2: Task 3.2 (quick, unblock Điều vận)
         Task 2.1.1–2.1.4 (state machine + API)

Day 3–5: Task 2.1.5–2.1.8 (Mobile + notification + test)
         Task 3.3.1–3.3.3 (Hồ sơ xe)

Week 2:
Day 1–3: Task 3.1.1–3.1.5 (Recurring core)
         Task 3.3.4–3.3.6 (Hồ sơ tài xế + test)

Day 4–5: Task 3.1.6–3.1.7 (Clone + test)
         Integration testing full flow
         Bug fixes + polish
```

---

## 🎨 UI Implementation Plan

### Design Tokens cần confirm trước khi code
- [ ] Status colors: Draft, Chờ fill, Chờ duyệt, Đã duyệt, Từ chối, Lưu trữ
- [ ] Warning color cho cảnh báo vượt ngưỡng
- [ ] Disabled state styles (opacity, cursor)

### Components cần tạo mới
| Component | Dùng ở | Priority |
|---|---|---|
| `StatusBadge` | Toàn bộ phiếu | P0 |
| `ApprovalSection` | Màn hình Điều vận | P0 |
| `PdfExportButton` | Chi tiết phiếu | P0 |
| `RejectDialog` | Mobile App | P0 |
| `RecurringConfigForm` | Form tạo phiếu | P1 |
| `TripListCard` | Danh sách chuyến | P1 |
| `StudentCountField` | Chi tiết chuyến | P1 |
| `CostLimitAlert` | Dashboard | P1 |
| `FileAttachmentUpload` | Chi tiết phiếu | P1 |
| `VehicleProfileCard` | Hồ sơ xe | P2 |
| `PriceReferenceLink` | Section nhập giá | P2 |

### Screens cần update
| Screen | Changes | Task |
|---|---|---|
| Form tạo phiếu | Ẩn trường giá, đổi label, thêm toggle Recurring | 2.1 + 3.1 |
| Chi tiết phiếu | Thêm section giá (read-only Người đề xuất), nút PDF disabled, Upload scan | 2.1 |
| Màn hình Điều vận | Thêm section nhập giá, link Bảng giá | 2.1 + 3.2 |
| Danh sách phiếu | Update status badge, filter trạng thái mới | 2.1 |
| Mobile — Trưởng đơn vị | Màn hình chi tiết + nút Duyệt/Từ chối | 2.1 |
| Settings (Admin) | Thêm field URL Bảng giá | 3.2 |
| Module Hồ sơ (mới) | Danh sách + Form Xe, Tài xế, NCC | 3.3 |
| Danh sách chuyến Recurring | View mới | 3.1 |

---

## 🔌 API Integration Plan

### Endpoints cần tạo mới

```typescript
// Approval Flow
PATCH  /api/phieu/:id/fill-gia       // Điều vận fill giá
PATCH  /api/phieu/:id/submit-duyet   // Submit lên Trưởng đơn vị
PATCH  /api/phieu/:id/duyet          // Trưởng đơn vị duyệt
PATCH  /api/phieu/:id/tu-choi        // Từ chối (required: reason)
GET    /api/phieu/:id/pdf            // Xuất PDF (auth + status check)
POST   /api/phieu/:id/attachment     // Upload scan phiếu đã ký

// Recurring
POST   /api/recurring                // Tạo recurring config
GET    /api/recurring/:id/chuyen     // Danh sách chuyến
PATCH  /api/chuyen/:id/si-so         // Cập nhật sĩ số (24h rule)

// Hồ sơ
CRUD   /api/xe
CRUD   /api/tai-xe
CRUD   /api/ncc

// Settings
GET    /api/settings/bang-gia-url
PUT    /api/settings/bang-gia-url    // Admin only
```

### Endpoints cần update
- `GET /api/phieu` — thêm filter by status mới
- `GET /api/phieu/:id` — thêm fields: gia, attachment_urls, recurring_id

---

## 🧪 Testing Plan

### Unit Tests
- [ ] State machine transitions (không skip bước)
- [ ] 24h deadline calculation (timezone-aware)
- [ ] Recurring date generation (skip weekend/holiday — khi applicable)
- [ ] Cost limit calculation (tổng tích lũy theo tháng)
- [ ] Permission checks (role + department)

### Integration Tests
- [ ] Happy path: Tạo phiếu → Fill giá → Duyệt → Xuất PDF → Upload scan
- [ ] Reject path: Từ chối → Resubmit → Duyệt
- [ ] Recurring: Tạo config → Generate chuyến → Cập nhật sĩ số
- [ ] Upload file: valid file, oversized file, wrong format

### E2E Tests (nếu applicable)
- [ ] Flow Người đề xuất từ đầu đến cuối
- [ ] Flow Điều vận từ tiếp nhận đến điều phối
- [ ] Flow Trưởng đơn vị trên Mobile App (manual nếu không có E2E tool)

### Regression Tests
- [ ] Tạo phiếu cũ (không Recurring) vẫn hoạt động
- [ ] Filter/search phiếu không bị ảnh hưởng
- [ ] Điều phối xe (flow cũ) không bị break

---

## 👀 Review Process

### Before Code Review
- [ ] Self-review với coding checklist (Phần 2 — Checklist 3)
- [ ] Chạy unit tests locally, không có test fail
- [ ] Test UI trên Desktop + Tablet minimum
- [ ] Không có console.log/debug code còn sót

### Code Review Criteria
- [ ] Đúng với AC (Acceptance Criteria) trong Brief
- [ ] Không có hardcode business values
- [ ] API calls đúng endpoint và phân quyền
- [ ] Error handling đầy đủ (happy path + edge cases)
- [ ] Component không render lại không cần thiết

### QA Checklist trước deploy
- [ ] Toàn bộ AC trong Brief được verify
- [ ] Không regression trên features cũ
- [ ] PDF export test với phiếu thật
- [ ] Push notification test trên device thật
- [ ] Upload file test với PDF, JPG, PNG, file oversized

---

## 🚀 Deployment Checklist

### Pre-deploy
- [ ] All tests passing (unit + integration)
- [ ] Code review approved
- [ ] Migration script (nếu có schema change) tested trên staging
- [ ] Settings: Admin đã config URL Bảng giá
- [ ] Environment variables updated (nếu có thêm config mới)

### Deploy
- [ ] Deploy to staging → smoke test 30 phút
- [ ] Verify push notification trên staging
- [ ] Verify PDF export trên staging
- [ ] Deploy to production (off-peak hours)

### Post-deploy
- [ ] Monitor error logs 1 giờ sau deploy
- [ ] Spot check 5 phiếu thật với flow đầy đủ
- [ ] Thông báo cho Điều vận team về tính năng mới
- [ ] Update SRS nếu có thay đổi so với brief

---

## ⚠️ Risks & Assumptions

| Risk | Impact | Mitigation |
|---|---|---|
| Race condition: 2 Trưởng đơn vị approve cùng lúc | High | Optimistic locking / status check trước khi update |
| Push notification delay trên mobile | Medium | Fallback: email notification |
| Recurring generate hàng nghìn chuyến → slow | Medium | Background job + progress indicator |
| PDF format không đúng khi in | High | Test với printer thật trước khi ship |
| Task 3.4 scope creep trong sprint | Medium | Hard boundary: grooming riêng, không start trước khi có SRS |
| URL Bảng giá không được config trước deadline | Low | Link ẩn gracefully, không block tính năng khác |

**Assumptions:**
- Mobile App đang dùng cùng authentication system với web
- Push notification infrastructure đã có (FCM/APNS setup)
- File storage (S3 hoặc tương đương) đã configured
- Department/phòng ban đã có trong DB, linked với user profile

---

# PHẦN 4 — PROMPT TEMPLATES

---

## 🤖 PROMPT 1: SETUP CURSOR (Initial)

```
Bạn là Senior Developer làm việc trên dự án VA Schools Điều Vận System.

## Context
- SRS: v1.3 (11/05/2026)
- Sprint: Post Demo 02
- Brief: [paste nội dung Dev Brief]

## Rules (LUÔN áp dụng)
- Đọc @.cursorrules trước khi làm bất cứ điều gì
- Kiểm tra @.cursor/ để hiểu conventions
- Tuân theo @.cursorignore

## Trước khi code bất kỳ task nào
1. Đọc brief/SRS của task đó
2. Xác nhận Acceptance Criteria
3. List các file sẽ bị ảnh hưởng
4. Xác nhận không break existing features
5. Confirm approach với tôi trước khi implement

## Coding Standards
- TypeScript strict mode
- Không hardcode business values (dùng constants)
- API calls qua service layer, không trực tiếp trong component
- Error handling đầy đủ — mọi async operation
- Unit test cho business logic

## Bắt đầu với task nào?
```

---

## 🤖 PROMPT 2: IMPLEMENT TASK CỤ THỂ

```
## Task: [Tên Task — VD: Task 2.1 Luồng duyệt 5 bước]

### Brief
[Paste nội dung task từ Dev Brief]

### Acceptance Criteria cần đạt
[Paste AC list]

### Yêu cầu thực hiện
1. Đọc @.cursorrules và @.cursor/ trước
2. Phân tích: list TẤT CẢ file bị ảnh hưởng
3. Xác định: file nào cần tạo mới / sửa / không đụng vào
4. Implement theo thứ tự: Data types → API service → Business logic → UI component → Integration
5. Sau mỗi sub-task: tóm tắt đã làm gì + file nào đã sửa
6. Cuối cùng: chạy checklist AC, confirm từng AC đã đạt chưa

### Constraints
- Không đổi field name trong DB/API (chỉ đổi UI label)
- Không break existing features (luôn kiểm tra regression)
- Giá dịch vụ: field disabled với Người đề xuất, không ẩn hoàn toàn
- Nút PDF: disabled khi chưa approved (không ẩn), kèm tooltip

### Bắt đầu với: phân tích file impact trước khi code
```

---

## 🤖 PROMPT 3: REVIEW CODE TRƯỚC PR

```
Review đoạn code sau theo tiêu chí:

## Code cần review
[paste code]

## Checklist review

### Business Logic
- [ ] Đúng với AC trong Brief không?
- [ ] Phân quyền role đúng chưa? (Ai được làm gì)
- [ ] Status transitions có đúng flow 5 bước không?
- [ ] 24h deadline logic có đúng không? (timezone-aware?)

### Code Quality
- [ ] Có hardcode value nào không? (giá, URL, deadline hours)
- [ ] Error handling đủ chưa? (network fail, 403, 409)
- [ ] API calls qua service layer chưa?
- [ ] Types đầy đủ? Không dùng `any`?

### UI/UX
- [ ] Loading state có không?
- [ ] Empty state có không?
- [ ] Error message user-friendly không?
- [ ] Disabled state có tooltip không?

### Performance
- [ ] List có pagination không?
- [ ] Có re-render không cần thiết không?

Trả về:
1. Issues tìm được (High/Medium/Low priority)
2. Suggestions cụ thể để fix
3. Overall assessment: Ready / Needs Work / Major Rework
```

---

## 🤖 PROMPT 4: XỬ LÝ FEEDBACK / CHỈNH SỬA

```
## Feedback mới nhận được
[Paste feedback từ client/PM/QA]

## Yêu cầu phân tích

### Bước 1: So sánh với BRD/SRS hiện tại
Feedback này:
- [ ] Thay đổi AC đã có trong Brief?
- [ ] Thêm yêu cầu mới ngoài scope?
- [ ] Contradicts với SRS v1.3?
- [ ] Chỉ là UX polish (không thay đổi logic)?

### Bước 2: Impact Analysis
List ra:
- Files bị ảnh hưởng: [list]
- API endpoints bị ảnh hưởng: [list]
- Database schema thay đổi không?: Yes/No
- Ảnh hưởng đến features khác: [list]
- Estimate thêm: [X giờ/ngày]

### Bước 3: Đề xuất hướng xử lý
Cho 2–3 approach với trade-off rõ ràng:
- Option A: [mô tả] — Pros: ... Cons: ...
- Option B: [mô tả] — Pros: ... Cons: ...

### Bước 4: Implement (sau khi chọn option)
- Không xóa code cũ ngay (comment out + ghi rõ reason)
- Không phá flow cũ của các task đã ship
- Giữ naming convention và component patterns cũ
- Maintain UI/UX consistency với phần còn lại

### Bước 5: Verify Checklist sau chỉnh sửa
- [ ] AC gốc vẫn còn pass
- [ ] Feedback mới đã được address
- [ ] Không regression trên features liên quan
- [ ] UI consistent với màn hình xung quanh
- [ ] API response format không thay đổi (backward compatible)

Bắt đầu bằng Bước 1: phân tích feedback này có nằm trong scope Brief không?
```

---

## 🤖 PROMPT 5: DEBUG / FIX BUG

```
## Bug Report
**Mô tả:** [Mô tả bug]
**Steps to reproduce:** [Steps]
**Expected:** [Expected behavior]
**Actual:** [Actual behavior]
**Environment:** [Staging/Production, Browser/Mobile]

## Yêu cầu debug

1. Đọc lại AC liên quan trong Brief — behavior mong đợi là gì?
2. Tìm root cause (không fix symptom)
3. Kiểm tra: bug này có ảnh hưởng flow khác không?
4. Propose fix: minimal change, không refactor lớn
5. Sau fix: list regression test cần chạy

### Không làm khi fix bug:
- Không thay đổi logic của flow khác
- Không rename variables/functions (phá git blame)
- Không "tận dụng" để refactor thêm
- Không hardcode workaround

Phân tích và đề xuất fix:
```

---

## 🤖 PROMPT 6: GENERATE CHECKLIST VERIFY SAU CHỈNH SỬA

```
Tôi vừa thực hiện thay đổi sau:
[Mô tả thay đổi]

Files đã sửa:
[List files]

Hãy generate checklist verify dựa trên:
1. AC của task liên quan trong Brief
2. Các flow có thể bị ảnh hưởng
3. UI/UX consistency check
4. Regression risk assessment

Format output:
## ✅ VERIFY CHECKLIST — [Tên thay đổi]

### AC Verification
- [ ] AC1: [test case cụ thể]
- [ ] AC2: ...

### Regression Tests
- [ ] [Feature A]: [test case]
- [ ] [Feature B]: ...

### UI/UX Check
- [ ] ...

### Edge Cases
- [ ] ...

### Sign-off
- [ ] Dev self-test: Done
- [ ] QA test: Done
- [ ] Lead review: Done
```

---

# PHẦN 5 — RISK NOTES

## 🔴 High Risk

### 1. Luồng duyệt 5 bước — Race Condition
**Scenario:** Trưởng đơn vị A và B cùng duyệt phiếu cùng lúc (nếu có bug phân quyền)  
**Mitigation:** Server-side optimistic locking — check status trước khi update, return 409 nếu đã thay đổi

### 2. PDF Export — Trước khi Duyệt
**Scenario:** Frontend bypass disabled button → gọi API trực tiếp  
**Mitigation:** API `/pdf` bắt buộc check status == "Đã duyệt" ở server side, không chỉ check ở UI

### 3. Task 3.4 Scope Creep
**Scenario:** Team bắt đầu implement P2P Học sinh chính sách mà không có SRS đầy đủ  
**Mitigation:** Hard stop — task 3.4 chỉ được bắt đầu sau buổi grooming riêng và SRS được approve

## 🟡 Medium Risk

### 4. Recurring Generate — Performance
**Scenario:** User tạo recurring 1 năm, weekly, 5 ngày/tuần → ~260 records  
**Mitigation:** Background job (queue), không sync call; hiển thị progress; set max limit hợp lý

### 5. Push Notification Reliability
**Scenario:** Trưởng đơn vị không nhận notification → không biết có phiếu chờ  
**Mitigation:** In-app badge/counter + email fallback; màn hình "Phiếu chờ tôi duyệt" luôn accessible

### 6. Phiếu Cũ Có Field "Dùng cho 3+ ngày"
**Scenario:** Phiếu cũ trong DB có trường này → mở lại bị lỗi validation  
**Mitigation:** Migration script xử lý dữ liệu cũ; code mới gracefully ignore field này nếu tồn tại

## 🟢 Low Risk

### 7. URL Bảng Giá Không Được Config
**Mitigation:** Link tự ẩn khi URL = null/empty → đã có trong AC, không crash

### 8. File Upload Format Bypass
**Mitigation:** Validate MIME type server-side (không chỉ extension)

---

# PHẦN 6 — BEST PRACTICES

## 6.1 AI-Assisted Workflow

```
Nguyên tắc làm việc với Cursor trong dự án này:

1. LUÔN đọc Brief/SRS trước khi code
   → Prompt Cursor: "Đọc brief này trước: [paste]"

2. LUÔN confirm impact analysis trước khi implement
   → Không để Cursor tự ý sửa file ngoài scope

3. LUÔN dùng Checklist để verify output
   → Sau mỗi task, run qua Checklist 1+2+3

4. KHÔNG để Cursor refactor khi đang fix bug
   → Tách riêng: bug fix PR và refactor PR

5. KHÔNG hardcode — nhắc Cursor mỗi khi thấy magic value
   → "Đổi 24 thành constant STUDENT_COUNT_UPDATE_DEADLINE_HOURS"
```

## 6.2 Team Collaboration

```
Git workflow:
- Branch: feature/task-2.1-approval-flow
- Commit prefix: feat/fix/refactor/chore/test
- PR description: paste AC list + self-test checklist
- PR không merge khi còn test fail

Code review:
- Reviewer đọc Brief trước khi review PR
- Comment phải reference AC (VD: "AC3 chưa được handle")
- Approve only khi tất cả checklist items passed

Communication:
- Block ngay khi không rõ requirement (không assume)
- Update Brief/SRS ngay khi có thay đổi (trước khi code)
- Mọi thay đổi scope sau 14/05 → update vào SRS trước khi start
```

## 6.3 Production-Ready Standards

```
Trước khi ship bất kỳ task nào:
✅ Tất cả AC verified bởi dev + QA
✅ Không regression (chạy regression test suite)
✅ Error handling: mọi API call có try-catch + user message
✅ Loading state: mọi async action có loading indicator
✅ Mobile test: tối thiểu test trên 1 Android + 1 iOS (nếu có Mobile)
✅ Console clean: không có warning/error không cần thiết
✅ Không có TODO comment trong production code
✅ Deploy notes: ghi lại config mới, migration mới, breaking changes
```

---

*Tài liệu này được tổng hợp từ Dev Brief Sprint Post Demo 02 | VA Schools | 14/05/2026*  
*Cập nhật tài liệu này khi có thay đổi requirement từ BA Team.*
