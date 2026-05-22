# Kế hoạch nâng cấp — Chi tiết phiếu điều xe (Staff + Portal)

Tài liệu này **ưu tiên và lộ trình setup** cho nâng cấp chức năng + UI/UX trang chi tiết yêu cầu (`/mng/requests/{id}`, portal tương đương). Bám domain: `docs/PROJECT_OVERVIEW.md`, quyền: `docs/PERMISSION_AND_ROLE.md`.

**Trạng thái:** kế hoạch — chưa bắt đầu Phase 1 (cập nhật cột *Status* khi triển khai).

---

## Mục tiêu

| # | Mục tiêu | Đo lường thành công |
|---|----------|---------------------|
| G1 | Người dùng biết **việc tiếp theo** trên một phiếu | ≤ 2 cú click từ header/timeline tới hành động (điền giá, duyệt, tài liệu) |
| G2 | **Dự toán** phản ánh dữ liệu thật (snapshot + giá điều vận) | Không còn “0 đ” khi chưa khai báo; có breakdown hoặc gợi ý bảng giá |
| G3 | **Tài liệu** rõ tiến độ ký / scan / OCR | Checklist + upload feedback; deep link `?tab=docs` ổn định |
| G4 | Giảm độ phức tạp `RequestDetailView.vue` | Tách ≥ 3 component/composable; file view < ~1200 dòng |

---

## Nguyên tắc triển khai

1. **Portal parity:** component dùng chung (`RequestDocsPanel`, cost card sau khi tách) — staff có thêm quyền.
2. **Không phá API:** mở rộng presenter / snapshot JSON trước khi breaking change.
3. **Feature flag (tùy chọn):** `DISPATCH_PRICING_SUGGEST_ENABLED` cho gợi ý bảng giá.
4. Mỗi phase: **test Feature** (Laravel) + smoke UI (tab, query string).
5. Nhánh Git: `feat/request-detail-p0-workflow`, `feat/request-detail-p1-pricing`, … merge nhỏ.

---

## Thứ tự ưu tiên (đã chốt)

```
P0 Workflow & điều hướng  →  P0 Dự toán (breakdown + deep link)
        ↓
P0 Tài liệu UX            →  P1 Gợi ý bảng giá (API + wizard + detail)
        ↓
P1 Điền giá / duyệt      →  P1 OCR queue (nếu có hạ tầng)
        ↓
P2 Map km, dashboard, audit UI
```

**Lý do:** Timeline + tab badge giải “phiếu đang kẹt ở đâu” ngay; dự toán và tài liệu song song P0; bảng giá thông minh cần backend — đặt P1 sau khi UX điều hướng ổn.

---

## Phase 0 — Nền UX & dữ liệu hiển thị (≈ 1–2 sprint)

### P0.1 — Workflow timeline + tab badges

| Hạng mục | Chi tiết |
|----------|----------|
| **Deliverable** | `useRequestWorkflowSteps.js` + `RequestWorkflowBar.vue` (hoặc mở rộng stepper hiện có) |
| **Bước hiển thị** | Nháp/chờ giá/chờ duyệt/đã duyệt/thiếu phiếu ký/thiếu scan/đã nhận giấy (tùy `status`, `paper_status`, attachments) |
| **Tab** | Badge số: form (fill price + dept), docs (signed thiếu) |
| **Deep link** | `?tab=form&focus=fill-price`, `?tab=docs` — scroll + `scroll-margin` + highlight ring 3s |
| **Files chính** | `RequestDetailView.vue`, `PortalRequestDetailView.vue`, `vi.json` / `en.json` |
| **Acceptance** | Dispatcher mở phiếu `pending` → thấy “Điền giá” → 1 click tới section; `approved` thiếu signed → badge docs |

**Tasks checklist**

- [ ] Định nghĩa `workflowSteps` computed (mirror `docsProgressSteps` + price/dept)
- [ ] Component timeline (mobile: dọc; desktop: ngang compact)
- [ ] `watch(route.query)` mở rộng `focus` param
- [ ] Sidebar block “Việc cần làm” (3 bullet theo role)
- [ ] Test: query `?tab=docs` từ email reminder (đã có) vẫn hoạt động

---

### P0.2 — Dự toán: breakdown + điều hướng

| Hạng mục | Chi tiết |
|----------|----------|
| **Deliverable** | `RequestCostEstimateCard.vue` tách từ view |
| **UI** | Accordion: phụ phí form, từng dòng passenger/business/cargo, cầu đường; tổng; dòng `service_price` nếu có |
| **CTA** | “Chỉnh trong biểu mẫu” → `?tab=form` |
| **Data** | Giữ đọc `wizard_snapshot`; không API mới trong P0.2 |
| **Acceptance** | Phiếu chưa nhập giá: tổng `—` + hint; có `unit_price`: breakdown khớp tổng |

**Tasks checklist**

- [ ] Extract `costEstimate` → composable `useRequestCostEstimate.js`
- [ ] Component + unit test logic tổng (Vitest nếu có, hoặc test PHP presenter sau)
- [ ] Badge km trên lộ trình đồng bộ `distanceLabel` (đã có field snapshot nếu điền sau)

---

### P0.3 — Tài liệu UX

| Hạng mục | Chi tiết |
|----------|----------|
| **Deliverable** | Checklist trên `RequestDocsPanel`; upload success animation/toast |
| **OCR** | Label trạng thái trên row; disable nút khi `ocrBusy` (đã có — polish copy i18n) |
| **i18n** | Đưa “Chọn file / Tải lên” trong `FileUpload.vue` vào locale |
| **Acceptance** | Upload xong file xuất hiện không F5; checklist 3 mục click scroll tới section |

**Tasks checklist**

- [ ] `docsChecklist` computed trong `useDispatchRequestDocs.js`
- [ ] `FileUpload` emit rõ `uploaded` → panel prepend highlight class
- [ ] Portal: verify `canUpload*` props parity

---

### P0 — Definition of Done

- [ ] `npm run build` pass
- [ ] `php artisan test --filter=DispatchRequest` (hoặc subset fill price + signed paper)
- [ ] Manual: 1 phiếu `pending`, 1 `approved` thiếu signed, 1 có scan
- [ ] Cập nhật mục *Status* Phase 0 trong file này → `Done`

---

## Phase 1 — Dữ liệu & nghiệp vụ sâu (≈ 2–3 sprint)

### P1.1 — Gợi ý bảng giá (backend + UI)

| Hạng mục | Chi tiết |
|----------|----------|
| **API** | `GET /api/pricing/suggest` (query: `trip_type`, `origin`, `destination`, `passenger_count`) |
| **Service** | `PricingSuggestionService` — đọc bảng giá hiện có (`pricing` module) |
| **Lưu snapshot** | POST create/patch wizard: optional `form.pricing_suggestion_id`, `estimated_distance_km`, `reference_unit_price` |
| **UI Detail** | Modal bảng giá: nút **Áp dụng** → PATCH snapshot (staff `request.update` hoặc quyền riêng) |
| **Wizard** | Step 3 panel “Gợi ý” (không chặn submit) |
| **Config** | `.env` `DISPATCH_PRICING_SUGGEST_ENABLED=true` |
| **Tests** | `tests/Feature/PricingSuggestionTest.php` |

**Tasks checklist**

- [ ] Migration không bắt buộc nếu chỉ JSON snapshot
- [ ] Route + policy
- [ ] Frontend `api/pricing.js` + modal actions
- [ ] Feature test + doc `docs/API_OVERVIEW.md` một dòng endpoint

---

### P1.2 — Điền giá & duyệt (minh bạch)

| Hạng mục | Chi tiết |
|----------|----------|
| **UI Form tab** | Banner: tổng khai báo vs `service_price` (lệch → cảnh báo vàng) |
| **Presenter** | Trả `price_filled_by` (user name), `price_filled_at` trên GET request |
| **Dept** | Preview 2 số trước nút Duyệt |
| **Tests** | Mở rộng `DispatchRequestFillPriceDeptHeadTest` |

---

### P1.3 — OCR nền (tùy hạ tầng)

| Hạng mục | Chi tiết |
|----------|----------|
| **Job** | `ProcessAttachmentOcrJob` — thay stub sync nếu cần |
| **UI** | Polling hoặc notification khi xong |
| **So khớp** | Rule đơn giản: tìm số tiền trong `ocr_text` vs `service_price` → badge |
| **Doc** | `docs/THIRD_PARTY_SERVICES.md` |

**Gate:** chỉ start khi credential OCR có trên staging.

---

### P1 — Definition of Done

- [ ] Suggest API có test; tắt flag → UI ẩn gợi ý
- [ ] Áp dụng bảng giá cập nhật snapshot + refresh detail
- [ ] Phase 1 status → `Done` trong file này

---

## Phase 2 — Mở rộng (backlog)

| ID | Hạng mục | Ghi chú |
|----|----------|---------|
| P2.1 | Map / km (Maps API) | Xác nhận chi phí API; lưu snapshot sau confirm |
| P2.2 | Widget dashboard thiếu giá / thiếu tài liệu | Query index có filter |
| P2.3 | Audit timeline trên detail | Đọc `audit_logs` |
| P2.4 | Diff phiếu đặt lại | So `wizard_snapshot` 2 request |
| P2.5 | Lazy tab BM.03 | `defineAsyncComponent` |

---

## Setup môi trường & quy trình

### Trước Phase 0

```bash
# Local
cp .env.example .env   # nếu chưa
composer install
npm ci
php artisan migrate
php artisan db:seed --class=RbacSeeder   # nếu cần quyền test

# Nhánh
git checkout -b feat/request-detail-p0-workflow
```

### Biến môi trường (theo phase)

| Phase | Biến | Mặc định |
|-------|------|----------|
| P0 | (không bắt buộc) | — |
| P1 pricing | `DISPATCH_PRICING_SUGGEST_ENABLED` | `false` |
| P1 OCR | (theo `docs/THIRD_PARTY_SERVICES.md`) | — |
| Nhắc giấy (đã có) | `DISPATCH_SIGNED_PAPER_REMIND_*` | xem `config/dispatch.php` |

### Cron / queue (production)

- `php artisan schedule:run` — nhắc upload phiếu ký (`dispatch:remind-signed-paper-upload`)
- Worker queue nếu bật P1.3 OCR job

### Roles để test manual

| Vai trò | Kiểm tra |
|---------|----------|
| Requester (portal) | Upload signed, xem docs |
| Dispatcher | Fill price, paper scan, OCR |
| Dept head | Approve/reject, thấy tổng giá |
| `request.view_all` | Toàn bộ tab staff |

---

## Phân công gợi ý (1 team nhỏ)

| Phase | Backend | Frontend | QA |
|-------|---------|----------|-----|
| P0.1 | — | Lead | Smoke tab/query |
| P0.2 | — | Lead | Số tiền breakdown |
| P0.3 | — (i18n only) | Lead | Upload/OCR |
| P1.1 | Lead | Support | API + áp dụng |
| P1.2 | Presenter | Form tab | Fill + dept |
| P1.3 | Lead OCR | Status UI | Queue |

---

## Rủi ro & giảm thiểu

| Rủi ro | Giảm thiểu |
|--------|-------------|
| `RequestDetailView` quá lớn, merge conflict | Tách component ngay P0.2 |
| Gợi ý giá sai tuyến | Suggest chỉ “tham khảo”; bắt buộc xác nhận khi áp dụng |
| OCR chi phí | Flag + queue; giới hạn rate (đã có `attachment-ocr`) |
| Portal lệch staff | Một PR P0 gồm cả `PortalRequestDetailView` |

---

## Theo dõi tiến độ

Cập nhật bảng khi hoàn thành từng phase:

| Phase | Status | Ngày bắt đầu | Ngày xong | PR / ghi chú |
|-------|--------|---------------|-----------|--------------|
| P0.1 Workflow | Done | 2026-05-22 | 2026-05-22 | Workflow bar, tab badges, focus query |
| P0.2 Cost card | Done | 2026-05-22 | 2026-05-22 | RequestCostEstimateCard + composable |
| P0.3 Docs UX | Done | 2026-05-22 | 2026-05-22 | Checklist, highlight, FileUpload i18n |
| P1.1 Pricing suggest | Done | 2026-05-22 | 2026-05-22 | API + apply pricing-hints |
| P1.2 Fill/dept | Done | 2026-05-22 | 2026-05-22 | price_filled_by_user, dept banner |
| P1.3 OCR | Done | 2026-05-22 | 2026-05-22 | Optional queue via DISPATCH_OCR_USE_QUEUE |
| P2.2 Ops alerts (list) | Done | 2026-05-22 | 2026-05-22 | `stats.ops` + RequestOpsAlertsBar |
| P2.3 Audit timeline | Done | 2026-05-22 | 2026-05-22 | GET audit-logs + RequestAuditTimeline |
| P2.4 Clone lineage | Done | 2026-05-22 | 2026-05-22 | `cloned_from_summary` banner |
| P2.5 Lazy BM.03 tab | Done | 2026-05-22 | 2026-05-22 | defineAsyncComponent |
| P2.1 Maps link (external) | Partial | 2026-05-22 | | Google Maps dir URL; no Distance Matrix API |
| P2.x Dashboard widget | Backlog | | | |
| P2.4 Diff wizard snapshot | Backlog | | | Beyond lineage banner |

---

## Liên kết code hiện tại

| Khu vực | File |
|---------|------|
| Detail staff | `resources/js/src/views/requests/RequestDetailView.vue` |
| Detail portal | `resources/js/src/views/portal/PortalRequestDetailView.vue` |
| Tài liệu | `resources/js/src/components/requests/RequestDocsPanel.vue` |
| Docs logic | `resources/js/src/composables/useDispatchRequestDocs.js` |
| Dự toán (computed) | `costEstimate` trong `RequestDetailView.vue` → tách P0.2 |
| Fill price API | `DispatchRequestController`, `FillPriceDispatchRequestRequest` |
| Nhắc phiếu ký | `SignedPaperUploadReminderNotification`, `config/dispatch.php` |

---

## Bước tiếp theo (hành động ngay)

1. Tạo nhánh `feat/request-detail-p0-workflow`.
2. Implement **P0.1** (timeline + tab badges + `focus` query) — PR đầu tiên nhỏ nhất có giá trị.
3. PR2: **P0.2** + **P0.3** có thể gộp nếu cùng người frontend.
4. Sau khi P0 Done → kickoff P1.1 (API suggest) với flag tắt trên production.

*Chỉnh sửa kế hoạch: PR riêng cập nhật file này, không rải roadmap vào nhiều doc.*
