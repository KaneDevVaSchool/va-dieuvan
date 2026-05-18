# PORTAL/NEW — Cấu trúc & Kế hoạch phát triển

> Route: `https://dieuvan.vaschools.edu.vn/portal/new`  
> Vue route name: **`portalCreate`** · Component: `resources/js/src/views/portal/PortalCreateView.vue`  
> Đây là **wizard tạo yêu cầu điều xe** dành riêng cho `internal_user` (portal-only user).

---

## Mục lục

1. [Bối cảnh & mục tiêu](#1-bối-cảnh--mục-tiêu)
2. [Phân quyền truy cập](#2-phân-quyền-truy-cập)
3. [Cây layout & component](#3-cây-layout--component)
4. [Luồng 4 bước (stepper)](#4-luồng-4-bước-stepper)
   - [Bước 1 — Loại chuyến](#bước-1--loại-chuyến-step-0)
   - [Bước 2 — Thông tin](#bước-2--thông-tin-step-1)
   - [Bước 3 — Chi tiết](#bước-3--chi-tiết-step-2)
   - [Bước 4 — Xác nhận & Gửi](#bước-4--xác-nhận--gửi-step-3)
5. [State management & composable](#5-state-management--composable)
6. [Draft cục bộ (localStorage)](#6-draft-cục-bộ-localstorage)
7. [Urgent auto-detection](#7-urgent-auto-detection)
8. [API calls](#8-api-calls)
9. [Modals (Teleport)](#9-modals-teleport)
10. [Điểm khác biệt Portal vs Staff wizard](#10-điểm-khác-biệt-portal-vs-staff-wizard)
11. [Kế hoạch cải thiện & gap hiện tại](#11-kế-hoạch-cải-thiện--gap-hiện-tại)

---

## 1. Bối cảnh & mục tiêu

`/portal/new` là **entry point duy nhất** để người dùng nội bộ không có quyền dispatcher/admin tạo yêu cầu điều xe. Người dùng chỉ thấy shell portal (không thấy sidebar staff), được dẫn vào wizard 4 bước, điền thông tin và nhận kết quả ngay trên màn hình.

**Mục tiêu UX:**
- Đơn giản, mobile-first (PWA)
- Tự lưu nháp vào localStorage để không mất dữ liệu khi reload
- Gửi xong thấy ngay ID yêu cầu + tùy chọn xem chi tiết / tạo tiếp

---

## 2. Phân quyền truy cập

| Điều kiện | Kết quả |
|-----------|---------|
| User có `canAccessDispatchWebApp()` | Redirect → `/staff` (dashboard) |
| User có `canAccessDriverWebApp()` nhưng không có staff | Redirect → `/driver` |
| User không có cả hai (**portal user**) | Được ở trong `/portal/*` |
| Chưa đăng nhập | Redirect → `/login?redirect=/portal/new` |

Guard xử lý trong `router/index.js` → `beforeEach`.  
Role thực tế: chủ yếu `internal_user`; không yêu cầu permission cụ thể ngoài xác thực.

---

## 3. Cây layout & component

```
/portal  →  PortalLayout.vue
             ├── PortalHeader.vue           (fixed top bar: logo, user menu, notification bell)
             └── <RouterView>
                  └── PortalCreateView.vue  (/portal/new)
                       ├── <header>         (title, breadcrumb, action bar)
                       │    ├── Nút Hủy
                       │    ├── Nút Lưu nháp  + flash "Đã lưu"
                       │    ├── Nút Thư viện nháp  (mở draftsModal)
                       │    ├── Nút Xóa nháp hiện tại  (mở clearDraftModal)
                       │    └── Nút Primary  (Tiếp theo / Gửi yêu cầu)
                       ├── PortalStepper.vue  (interactive, 4 steps)
                       ├── <section .rounded-2xl>  (main card)
                       │    ├── Step 0: grid 4 loại chuyến  (inline buttons)
                       │    ├── Step 1: form thông tin       (inline)
                       │    │    └── RecurringConfigSection.vue
                       │    ├── Step 2: DispatchWizardStep3.vue  (async lazy)
                       │    │    ├── DispatchStepDetails.vue  (variant=passenger|business)
                       │    │    └── [cargo rows inline]
                       │    └── Step 3: ConfirmSummary.vue
                       │         ├── SummarySection.vue
                       │         └── PdfPreview.vue  (after submit)
                       └── <Teleport to="body">
                            ├── clearDraftModal
                            ├── submitResultModal
                            └── draftsModal  (thư viện nháp)
```

**Component portal dùng chung** (trong `components/portal/`):

| Component | Vai trò |
|-----------|---------|
| `PortalHeader.vue` | Top bar fixed: logo VAS, bell thông báo, avatar/menu |
| `PortalStepper.vue` | Thanh tiến độ 4 bước, interactive (nhảy bước đã đi qua) |
| `PortalKpiCards.vue` | Thẻ KPI trang home (pending, processing, completed…) |
| `PortalQuickActions.vue` | Sidebar quick-link trang home |
| `PortalNotificationsPanel.vue` | Panel thông báo sidebar trang home |
| `PortalRequestCard.vue` | Card yêu cầu (list view) |
| `PortalRequestsTable.vue` | Bảng yêu cầu trang home |
| `PortalStatusTimeline.vue` | Timeline trạng thái trang detail |
| `PortalTripTypeGrid.vue` | Grid chọn loại chuyến (được tích hợp inline vào Step 0) |
| `PortalSignedDocUpload.vue` | Upload tài liệu đã ký |
| `PortalSuccessCard.vue` | Card thành công sau submit |
| `PortalStatusHint.vue` | Hint giải thích trạng thái |
| `PortalEmptyState.vue` | Empty state trang list |
| `PortalRequestSkeleton.vue` | Skeleton loading |

---

## 4. Luồng 4 bước (stepper)

### Bước 1 — Loại chuyến (`step === 0`)

**Mô tả:** Người dùng chọn 1 trong 4 loại chuyến.

| Giá trị `trip_type` | Nhãn | Icon |
|---------------------|------|------|
| `door_to_door` | D2D / Cửa–cửa | `AcademicCapIcon` |
| `point_to_point` | Điểm–điểm | `BuildingOffice2Icon` |
| `business` | Công tác | `BriefcaseIcon` |
| `cargo` | Hàng hóa | `CubeIcon` |

**Hành vi:**
- Click vào loại đang chọn → tự động `nextStep()` (double-tap shortcut, aria hint)
- Query param `?type=door_to_door|point_to_point|business|cargo` → skip sang Step 1 với type preset
- Loại `point_to_point` mở thêm sub-option `purpose_kind`: `point_to_point` hoặc `extracurricular`

**Validation:** Phải chọn 1 loại mới `canGoNext`.

---

### Bước 2 — Thông tin (`step === 1`)

**Cấu trúc form (7 nhóm):**

#### A. Người đề nghị (`sec_requester`)
- Ô tìm theo tên (autocomplete debounce, combobox ARIA, ≥2 ký tự gọi API)
- Họ tên đầy đủ `*`
- Email `*` (validate format khi blur)
- Số điện thoại (numeric, maxlength 11)
- Đơn vị / bộ phận

> API: `searchUsersForDispatchForm` → `GET /api/users/search-for-dispatch-form`

#### B. Thời gian (`sec_time`)
- Ngày đề xuất `*` (date picker)
- Ngày giờ cần xe `*` (datetime-local; validate thứ tự: ngày cần ≥ ngày đề xuất)
- **Urgent toggle** (xem mục 7) + lý do khẩn `*` (khi urgent = true)

#### C. Mục đích (`sec_purpose`)
- Radio `purpose_kind` (chỉ hiện khi `point_to_point`)
- `RecurringConfigSection` — cấu hình lặp lịch (xem component riêng)
- Textarea mục đích `*`
- Upload văn bản căn cứ (drag-drop / click; accept PDF/image; hiển thị tên file + size)

#### D. Đối tượng phục vụ (`sec_targets`)
- Danh sách checkbox cuộn được (`TARGET_OPTIONS`)
- Cảnh báo nếu `point_to_point` mà chưa chọn

#### E. Người phụ trách (`sec_coordinator`)
- Autocomplete tương tự Người đề nghị
- Họ tên, Email (validate), Số điện thoại

#### F. Kênh gửi (`source_channel`)
- Select disabled, cố định `portal` (readonly UI indicator)

**Validation step 1:**
- `requester_name`, `requester_email`, `proposed_date`, `requested_at`, `purpose` bắt buộc
- Email format check (requester + coordinator)
- Date order: `requested_at ≥ proposed_date`
- `urgent_reason` bắt buộc khi `is_urgent = true`

---

### Bước 3 — Chi tiết (`step === 2`)

Dùng chung component `DispatchWizardStep3.vue` (async lazy load) với staff wizard.

**Phân nhánh theo `trip_type`:**

#### `door_to_door` hoặc `point_to_point`
- **E.1 — Lịch hành khách** (`showE1Schedule`): bảng hàng lặp (`DispatchStepDetails variant="passenger"`)
  - Checkbox multi-day
- **E.1.1 — Thông tin bổ sung** (`showPassengerTripExtras && !isBusinessTrip`):
  - Cờ `3+ ngày` (`e1_use_3plus_days`)
  - Khoảng thời gian: từ ngày, đến ngày, tổng số ngày, chi phí thêm (VND)
  - Chọn thứ trong tuần (weekday strip chips)
- **E.2.1 — Dịch vụ thêm** (`showPassengerTripExtras`):
  - Đón tận nhà (`e2_door_pickup`): có chi phí tùy chọn
  - Các dịch vụ bổ sung khác

#### `business`
- **E.2 — Lịch công tác** (`showE2Schedule`): bảng hàng (`DispatchStepDetails variant="business"`)
- **E.2.1** — dịch vụ thêm giống trên

#### `cargo`
- Bảng cargo rows (`isCargoRowFilled`)
- Thông tin giao nhận, SLA, ghi chú

**Component dùng chung:**

| Component | Vai trò |
|-----------|---------|
| `DispatchStepDetails.vue` | Bảng nhập hàng lặp (passenger/business/cargo), add/remove row |
| `DispatchItemCard.vue` / `TripItemCard.vue` | Card mỗi hàng trong bảng |

---

### Bước 4 — Xác nhận & Gửi (`step === 3`)

Component: `ConfirmSummary.vue`

**Trạng thái chưa submit:**
- Tóm tắt đầy đủ form qua `SummarySection.vue`
- Nút "Gửi yêu cầu" (primary, full confirm)
- Nút "Lưu nháp" (fallback)

**Sau submit thành công (`created` !== null):**
- Hiển thị ID yêu cầu + badge trạng thái
- Nút "Xem yêu cầu" → `portalRequestDetail`
- Nút "Xuất PDF" → `exportPortalDispatchRequestPdf` (disabled 5 giây sau tạo: `pdfLockedAfterCreate`)
- Nút "Tạo yêu cầu mới"

**Submit flow:**
1. `createPortalDispatchRequest(form, idempotencyKey)` → `POST /api/portal/dispatch-requests`
2. Upload `basisFile` nếu có → `uploadAttachment`
3. Kết quả → `submitResultModal` (success/fail)

---

## 5. State management & composable

Toàn bộ state dùng chung composable:

```
useDispatchRequestWizard({ isPortal: true })
  ↳ resources/js/src/composables/useDispatchRequestWizard.js
```

Được `provide` xuống các component con qua `inject(DISPATCH_WIZARD_KEY)`.

**State chính:**

| Ref | Kiểu | Mô tả |
|-----|------|-------|
| `step` | `number` | Bước hiện tại (0–3) |
| `maxReachedStep` | `number` | Bước xa nhất đã tới (điều khiển stepper) |
| `form` | `Ref<object>` | Toàn bộ dữ liệu form |
| `loading` | `Ref<boolean>` | Đang gọi API |
| `created` | `Ref<object\|null>` | Kết quả sau submit |
| `error` | `Ref<string>` | Lỗi submit |
| `activeDraftId` | `Ref<string\|null>` | ID nháp đang active |
| `savedDraftsList` | `Ref<array>` | Danh sách nháp trong thư viện |

**Computed / flags portal-specific:**

| Key | Mô tả |
|-----|-------|
| `isPortal = true` | Dùng `createPortalDispatchRequest` thay vì `createDispatchRequest` |
| `canGoNext` | Validation từng bước |
| `headerPrimaryLabel` | "Tiếp theo" (step < 2) / "Xem tóm tắt" (step 2) / "Gửi yêu cầu" (step 3) |

---

## 6. Draft cục bộ (localStorage)

**Schema lưu trữ:**

```
dispatch-wizard-draft-list-u{userId}  →  { items: [{ id, savedAt, tripLabel, purposeLine }] }
dispatch-wizard-draft-item-{draftId}  →  { form, savedAt, step }
dispatch-wizard-draft-active-u{userId} → draftId | null
```

**Giới hạn:** `MAX_SAVED_DRAFTS` nháp / user (cũ nhất bị xóa khi vượt).

**Tính năng thư viện nháp (draftsModal):**
- Mở từ nút "Thư viện nháp" trên header
- Load nháp có sẵn → hiển thị `tripLabel + purposeLine + thời gian`
- Nút "Mở" → `loadDraftById` → restore form + bước
- Nút "Xóa" → `deleteDraftById`
- Nút "Biểu mẫu mới" → `startNewDraftSession`
- Badge "Đang dùng" hiển thị nháp active

**Migrate v1 → v2:** Composable tự chuyển key nháp đơn cũ sang format danh sách nhiều bản.

---

## 7. Urgent auto-detection

```
GET /api/dispatch-settings  →  { urgent_threshold_hours: N }
```

- Nếu `requested_at - now < N giờ` → `urgentAutoActive = true`, toggle bị disable
- Hiển thị banner đỏ giải thích: "Yêu cầu gấp vì cần xe trong vòng N giờ"
- User có thể bật thủ công nếu `!urgentAutoActive`

---

## 8. API calls

| Hành động | API function | Endpoint |
|-----------|-------------|---------|
| Load dispatch settings | `getDispatchFormSettings()` | `GET /api/dispatch-settings` |
| Tìm người đề nghị | `searchUsersForDispatchForm(q)` | `GET /api/users/search-for-dispatch-form` |
| Tìm người phụ trách | `searchUsersForDispatchForm(q)` | (cùng endpoint) |
| Tạo yêu cầu portal | `createPortalDispatchRequest(data, key)` | `POST /api/portal/dispatch-requests` |
| Upload căn cứ | `uploadAttachment(file, meta)` | `POST /api/attachments` |
| Export PDF | `exportPortalDispatchRequestPdf(id)` | `GET /api/portal/dispatch-requests/{id}/export-pdf` |
| Load request detail | `getDispatchRequest(id)` | `GET /api/dispatch-requests/{id}` |
| Load template (khi replace draft) | `getDispatchRequestWizardTemplate(id)` | `GET /api/dispatch-requests/{id}/wizard-template` |

**Idempotency:** `createPortalDispatchRequest` dùng `newIdempotencyKey()` + header `Idempotency-Key`.

---

## 9. Modals (Teleport)

| Modal | Trigger | Mô tả |
|-------|---------|-------|
| `clearDraftModal` | Nút "Xóa nháp" | Xác nhận xóa nháp hiện tại |
| `submitResultModal` | Sau submit | Success/Fail với action buttons |
| `draftsModal` | Nút "Thư viện nháp" | Danh sách nháp đã lưu |

Tất cả dùng `Transition` fade + `backdrop-blur` + `role="dialog" aria-modal="true"`.

---

## 10. Điểm khác biệt Portal vs Staff wizard

| Điểm | Portal (`/portal/new`) | Staff (`/dispatch-requests/new`) |
|------|----------------------|----------------------------------|
| Composable flag | `isPortal: true` | `isPortal: false` |
| API tạo | `createPortalDispatchRequest` | `createDispatchRequest` |
| API PDF | `exportPortalDispatchRequestPdf` | `exportDispatchRequestPdf` |
| `source_channel` | Cố định `portal` (disabled select) | Có thể chọn `portal/zalo/paper` |
| Layout | `PortalLayout` + `PortalHeader` | `StaffRouteOutlet` + `AppSidebar` |
| Sau submit | Link → `portalRequestList` | Link → staff `requests` |
| Stepper component | `PortalStepper.vue` | Stepper riêng trong staff wizard |
| Auth guard | `portalUser` only | `canAccessDispatchWebApp()` |

---

## 11. Kế hoạch cải thiện & gap hiện tại

### Ưu tiên cao

| # | Gap / Vấn đề | Đề xuất |
|---|-------------|---------|
| 1 | **Không có real-time validation step 1** — user phát hiện lỗi email/ngày khi blur, không tổng hợp trước khi Next | Thêm inline validation summary trước khi `nextStep()` |
| 2 | **`source_channel` disabled select** — UX kém, trông như bug | Thay bằng read-only badge/chip "Gửi qua Portal" |
| 3 | **Cargo flow chưa đầy đủ** — thiếu SLA due date ở Step 2 cho portal | Kiểm tra và bổ sung field SLA visible khi `trip_type=cargo` |
| 4 | **Không có autosave** — user phải bấm nút "Lưu nháp"; nếu đóng tab trước khi bấm sẽ mất | Thêm autosave debounce 2s sau mỗi keystroke |

### Ưu tiên trung bình

| # | Gap / Vấn đề | Đề xuất |
|---|-------------|---------|
| 5 | **Stepper không hiện % tiến độ mobile** — trên màn hình nhỏ chỉ thấy 2 bước, phải scroll | Hiển thị "Bước X/4" text dưới stepper mobile |
| 6 | **`PortalTripTypeGrid.vue` tồn tại nhưng Step 0 dùng inline buttons** — trùng lặp logic | Hợp nhất về `PortalTripTypeGrid` |
| 7 | **Không có breadcrumb "← Quay về trang chủ"** | Thêm breadcrumb/back link trong header |
| 8 | **Draft library không hiện `trip_type` icon** | Thêm icon loại chuyến vào mỗi draft card |

### Ưu tiên thấp / Nice-to-have

| # | Đề xuất |
|---|---------|
| 9 | Preview PDF ngay trong Step 4 thay vì chỉ sau submit |
| 10 | Đồng bộ draft lên server (draft API) để dùng được trên nhiều thiết bị |
| 11 | Thêm `aria-live` region để screen reader thông báo khi bước thay đổi |
| 12 | Dark mode support (Layout Portal chưa có `dark:` classes) |

---

## Tóm tắt file liên quan

```
resources/js/src/
├── views/portal/
│   ├── PortalLayout.vue                  ← Shell portal (header + outlet)
│   ├── PortalCreateView.vue              ← /portal/new  ★ ENTRY POINT
│   ├── PortalHomeView.vue                ← /portal
│   ├── PortalRequestListView.vue         ← /portal/requests
│   ├── PortalRequestDetailView.vue       ← /portal/requests/:id
│   └── PortalNotificationsView.vue       ← /portal/notifications
├── views/requests/dispatch-wizard/
│   ├── DispatchWizardStep3.vue           ← Bước 3 dùng chung
│   ├── DispatchStepDetails.vue           ← Bảng hàng lặp
│   ├── ConfirmSummary.vue               ← Bước 4 dùng chung
│   ├── SummarySection.vue
│   └── PdfPreview.vue
├── components/portal/                    ← 14 components
├── components/recurring/
│   └── RecurringConfigSection.vue       ← Cấu hình lịch lặp
├── composables/
│   ├── useDispatchRequestWizard.js      ← Composable chính
│   └── dispatchWizardConstants.js       ← Constants, initial form
└── router/index.js                      ← Route guard portal
```
